<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Turn as BaseTurn;
use DiplomacyOrm\Move;
use DiplomacyOrm\Support;
use DiplomacyOrm\InvalidUnitException;

use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Skeleton subclass for representing a row from the 'turn' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Turn extends BaseTurn {
	const dt = 4; // Timesteps per year.  Half of this is hard programmed in (Seasons)

	protected $mlog;

	public function __construct() {
		global $MLOG;
		$this->mlog = $MLOG;
	}

	/**
	 * Constructs a new turn.
	 *
	 * @param iMatch The match being played
	 * @param iTurn Last turn in match, null if this is the first turn
	 **/
	public static function create(Match $match, Turn $last_turn = null) {
		$o = new Turn;
		$o->setMatch($match);
		$o->setStatus('open');

		if ($last_turn instanceof Turn) {
			$o->setStep($last_turn->getStep()+1);
		}
		$o->save();
		return $o;
	}

	public function addOrder(Order $l) {
		if ($this->getStatus() !== 'open') {
			// One exception, retreats
$this->mlog->debug("$l instanceof Retreat = ". ($l instanceof Retreat ? 'yes':'no'));
			if ($this->getStatus() === 'require_retreats' && $l instanceof Retreat) {
				// Good
			} else {
				throw new TurnClosedToOrdersException($this . ' status is "'. $this->getStatus() .'", can only accept orders on "open" state');
			}
		}
		if (is_null($l->getUnitType())) {
			// Guess at the unit based on game state
			$states = StateQuery::create()
				->filterByTurn($this)
				->filterByOccupier($l->getEmpire())
				->filterByTerritory($l->getSource()->getTerritory())
				->find();

			if (count($states) == 1) {
				if (!is_object($states[0]->getUnit()))
					throw new \DiplomacyOrm\InvalidUnitException("It seems that there is no unit on territory ". $l->getSource()->getTerritory() . ", cannot issue order.");

				$l->setUnitType($states[0]->getUnit()->getUnitType());
			} else {
				throw new \DiplomacyOrm\InvalidUnitException("Could not determine unit");
			}
		}
		return parent::addOrder($l);
	}

	public function isSpring() {
		return ($this->getMatch()->getGame()->getStartSeason()+$this->getStep()) % self::N_seasons == 0;
	}

	/**
	 * English printout of the season
	 */
	public function getSeasonString() {
		return self::$seasons[$this->getStep()%self::N_seasons]['name'];
	}

	/**
	 * Season string
	 */
	public function getSeason() {
		return self::$seasons[$this->getStep()%self::N_seasons]['key'];
	}

	public function __toString() {
		$season = "[". $this->getPrimaryKey() . "]" . $this->getSeasonString() . ',';
		$year = $this->getMatch()->getGame()->getStartYear() + floor(($this->getStep()/self::N_seasons));

		return "$season $year";
	}

	public function printState() {
		$state = $this->state();
		$str .= str_pad('Territory', 30) . str_pad('Empire', 12) . str_pad('Unit', 10) . "\n";
		$str .= str_pad('', 29, '-') . ' ' . str_pad('', 11, '-') . ' '. str_pad('', 10, '-') . "\n";
		foreach ($state as $s) {
			$str .= str_pad($s[0], 30) . str_pad($s[1], 12) . $s[0]->getUnit() . "\n";
		}
		return $str;
	}

	/**
	 * Wrapper function for processing all orders.  Will
	 * validate orders, ensure the proper state of the turn
	 * and return what info (retreats) it needs to continue
	 */
	public function processOrders() {
		$this->validateOrders();
		$this->removeOrdersFromAttackedTerritories();
		$retreats = $this->resolveOrders();

print "Result $retreats\n";
		if ($retreats->getStatus() == ResolutionResult::SUCCESS) {
			$result = $this->carryOutOrders();
			return $result;
		} else {
			return $retreats;
		}
	}

	/** While orders should be validated before even being assigned
	 * to a turn, run this just in case.  This will call order->Validate
	 * which will ensure that all the parameters in an order make sense.
	 *
	 * It'll also ensure that no two orders have the same source.  Any
	 * orders originating from the same place are all invalidated.
	 * */
	protected function validateOrders() {
		$sources = array();
		$orders = $this->getOrders();
		foreach ($orders as $o) {
			if ($o->hasChildObject()) $o = Order::downCast($o);
			$o->validate();

			if ($o->failed()) continue;
			if (!array_key_exists($o->getSource()->getTerritoryId(), $sources))
				$sources[$o->getSource()->getTerritoryId()] = array();

// TODO make exception for CONVOYS
			$sources[$o->getSource()->getTerritoryId()][] = $o;
		}
		foreach ($sources as $orders) {
			if (count($orders) > 1) {
				foreach ($orders as &$o) {
					$o->fail("Order conflicts with '". join("', '", $orders) . "'");
				}
			}
		}
	}

	/**
	 * Iterates through the list of orders, and removes any order
	 * whose source territory is the attack destination of another
	 * empire.
	 *
	 * Motivation: If Ontario wants to attack New York, but Quebec attacks
	 *   Ontario, Ontario's attack order is canceled.
	 *
	 * TODO Put in exception for convoys
	 * TODO A territory might be able to have a fleet and an army, so two orders could be sourced from a territory
	 * */
	protected function removeOrdersFromAttackedTerritories() {
		$orders = $this->getOrders();
		foreach ($orders as &$order) {
			if ($order->failed()) continue;
			foreach ($orders as $ref) {
				if ($ref->failed()) continue;
				if ($order == $ref) continue;

				if ($order instanceof MultiTerritory
					&& $order->getSource()->getTerritory() == $ref->getDest()->getTerritory()
					&& $order->getSupporting() != $ref->getSupporting()
					// TODO convoy exception
				) {
					$order->fail("Source territory (". $order->source .") is being acted on by ". $ref->getEmpire() . " in '". $ref . "'");
				}
			}
		}
	}

	/**
	 * Most territories will not be acted upon.  This function filters our
	 * list of territories by which ones will be acted upon.
	 *
	 * This is easier than a DB query, as the sub-classing would make it
	 * hard to select source, dest, middle, etc..  This way we grab all the
	 * orders and they tell us what territories they're acting one
	 *
	 * return array(State)
	 */
	protected function getActiveStates() {
		$ret = array();
		$orders = $this->getOrders();
		foreach ($orders as $o) {
			$o = Order::downCast($o);
			$ts = $o->getActiveStates();
			$ret = array_merge($ret, $ts);
		}
		return $ret;
	}

	/**
	 * Build an array of states, along with the orders being acted upon them
	 *
	 * @return array(territory_template_id=>array('state' => State, 'orders' => array(Orders), 'tally' => PlayerMap), ...)
	 */
	protected function getStateOrderMap($include_failed = true) {
		$ret = array();

		// Could make these loops more efficient, but doing it in the
		// "easiest to read" way.
		// First filter the territories down
		$states = $this->getActiveStates();

		foreach ($states as &$s1) {
			$pkey = $s1->getTerritory()->getPrimaryKey(); // I don't think this matters, PK could be anything
			$ret[$pkey] = array('state' => $s1, 'orders' => array(), 'tally' => new PlayerMap($s1->getOccupier()));

			// Loop through all the states $s1
			// 	Loop through the orders
			// 		Loop through the states this order affects
			// 			If one of these $s2 states is $s1,
			// 				Add the order to our list.
			//
			// Basically, build a list of states that has a list of all the orders
			// acting on it.
			$orders = $this->getOrders();
			foreach ($orders as &$o) {
				$o = Order::downCast($o);
				$affected = $o->getActiveStates();
				foreach ($affected as $s2) {
					if ($s2->getTerritory() == $s1->getTerritory()) {
						if (!$include_failed && $o->failed()) continue;
						$ret[$pkey]['orders'][] = $o;
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * Build a list of territories from the Match and place it in $this->territories
	 * with the structure
	 * $territories[territory_id] = array(empire1_id => count, empire2_id => count, ...)
	 *
	 * Iterate through the orders, and increment the 'count' in the structure above every
	 * time a empire attacks a teritory or is supported.
	 *
	 * Then, iterate again through the orders, and mark every order that supports the
	 * winner as "success", and all others as "failed"
	 **/
	protected function resolveOrders() {
		// Limit our iterations to territories in play
		$states = $this->getStateOrderMap(false); // false to skip failed orders
		foreach ($states as &$map) {
			$state = $map['state'];
			$tally = $map['tally'];

			foreach ($map['orders'] as &$o) {
				if ($o->failed()) continue;

				if ($o instanceof Move) {
					if ($o->getDest() == $state) {
						$tally->inc($o->getEmpire(), $o);
					}
				} elseif ($o instanceof Support) {
					if ($o->getDest()->getTerritory() == $state->getTerritory()) {
						$tally->inc($o->getSupporting(), $o);
					}
				} elseif ($o instanceof Retreat) {
					// Nothing to do
				} else {
					trigger_error("Not sure how to perform <". get_class($o). "> $o");
				}
			}
		} unset($state); // Precaution

		foreach ($states as &$map) {
			// Find the winner of each territory
			$state = $map['state'];
			$winner = $map['tally']->findWinner()->winner();

			if (is_null($winner)) {
				continue;
			}

			// Loop through the orders again, fail all orders
			// who supported a loser, or lost a battle

			foreach ($map['orders'] as &$o) {
				if ($o->failed()) continue;
				if ($o instanceof Move) {
					if ($o->getDest() == $state && $o->getEmpire() != $winner) {
						$o->fail("Lost battle for ". $state->getTerritory(). " to $winner");
					}
				} elseif ($o instanceof Support) {
					if ($o->getDest() == $state && $o->getSupporting() != $winner) {
						$o->fail("Supported ". $o->getSupporting() . " in failed campaign against ". $state->getTerritory(). " that $winner won");
					}
				} elseif ($o instanceof Retreat) {
					// Nothing to do
				} else {
					trigger_error("Not sure how to perform <". get_class($o) . ">$o");
				}
			}
		} unset($state); // Precaution

		// Debug
		print "\n";
		print "Resolutions before retreats:\n";
		foreach ($states as &$map) {
			print $map['state']->getTerritory(). ", tally:\n";
			print $map['tally'];
			print "\n";
		}

		// Determine required retreats.  Go through the resolutions, and find all
		// occupiers who are not the winners
		$retreats = new ResolutionResult;
		foreach ($states as &$map) {
			$state = $map['state'];
			$winner = $map['tally']->winner();

			if (is_null($winner)) continue;

			if ($state->getOccupier() != $winner) {
				// Territory, loser, winner
				$retreats->addRequiredRetreat($state->getTerritory(), $state->getOccupier(), $winner);
			}
		} unset($state); // Precaution

		$orders = $this->getOrders();
		foreach ($orders as $o) {
			$o = Order::downCast($o);
			if ($o instanceof Retreat) {
				$retreats->addRetreat($o);
			}
		}
		$retreats->resolveRetreats();

		// Debug
		print "[Turn:resolveOrders]\n";
		print $retreats;

		// TODO Try to "Resolve" some retreats (any units we can kill off?)

		if ($retreats->getStatus() == ResolutionResult::RETREATS_REQUIRED) {
			$this->setStatus('require_retreats');
			$this->save();
			return $retreats;
		}

		if ($retreats->getStatus() == ResolutionResult::SUCCESS) {
			$this->setStatus('ready-to-execute');
			$this->save();
		}

		return $retreats;
	}

	/**
	 * Not sure what I intended for this function */
	function resolveRetreats() {

	}


	/**
	 * Actually execute the orders.
	 */
	function carryOutOrders() {
		if ($this->getStatus() != 'ready-to-execute') {
			throw new InvalidStateToExecute("State is ". $this->getState() . ", must be 'ready-to-execute'");
		}

		// First, create the "next" turn, and clone the state
		$nextTurn = $this->initiateNextTurn();

		// Orders would already be validated by this point, so no need to ensure
		// that territories are adjacent

		// --------------------------
		// Retreat orders

		$orders = OrderQuery::create()
			->filterByTurn($this)
			->filterByStatus('succeeded')
			->filterByDescendantClass('%Retreat', Criteria::LIKE)
			->find();
		foreach ($orders as $o) {
			$o = Order::downCast($o);
			print "Executing $o\n";

			// It must move to a space to	which it could ordinarily move if unopposed
			// by other units; that is, to an adjacent space suitable to an army
			// or fleet, as the case may be. The unit may not retreat, however, to
			// any space which is occupied, nor to the space its attacker came
			// from, nor to a space which was left vacant due to a standoff on the
			// move. If no place is available for retreat, the dislodged unit is
			// "disbanded"; that is, its marker is removed from the board.
			// http://diplom.org/Zine/F1995R/Loeb/rules.html

			// // Presumably, the order has been checked to see if the destination
			// // is occupied.  BUT, if two armies retreat to the same place, they
			// // are both disbanded.

			// TODO Lagragian units

			$nextSourceState = $this->getTerritoryNextState($o->getSource()->getTerritory());
			$nextSourceState->setOccupation();

			$nextDestState   = $this->getTerritoryNextState($o->getDest()->getTerritory());
			$nextDestState->setOccupation($o->getSource()->getOccupier(), $o->getSource()->getUnit());

			$o->addToTranscript('Executed');
		}

		// --------------------------
		// Move orders

		$orders = MoveQuery::create()
			->filterByTurn($this)
			->filterByStatus('succeeded')
			->find();
		foreach ($orders as $o) {
			$o = Order::downCast($o);
			print "Executing $o\n";

			// The unit we're going to move.
			$unit = $o->getSource()->getUnit();

			$nextSourceState = $this->getTerritoryNextState($o->getSource()->getTerritory());
			$nextSourceState->setUnit(null); // Keep occupying the territory, but the unit is moving

			$nextDestState   = $this->getTerritoryNextState($o->getDest()->getTerritory());
			$nextDestState->setOccupation($o->getSource()->getOccupier(), $unit);

			$unit->setState($nextDestState);
			$unit->setLastState($nextSourceState);

			if ($unit->getUnitType() == 'fleet' && $o->getSoure()->getTerritory()->getType() === 'water') {
				$this->mlog->debug("Setting last water territory on $unit to ". $o->getSoure()->getTerritory() ." on there move to ". $nextDestState->getTerritory() . "");
				$unit->setLastWater($o->getSoure()->getTerritory());
			}

			$o->addToTranscript('Executed');
		}



		// --------------------------
		// Disband orders

		$orders = OrderQuery::create()->filterByTurn($this)
			->filterByStatus('succeeded')
			->filterByDescendantClass('%Disband', Criteria::LIKE)
			->find();
		foreach ($orders as $o) {
			$o = Order::downCast($o);
			print "Executing $o\n";

			// This move can be over a convoy, whether that's valid will have
			// been determined before now.

			$nextSourceState = $this->getTerritoryNextState($o->getSource()->getTerritory());
			$nextSourceState->setOccupation();

			// TODO Lagragian units

			$o->addToTranscript('Executed');
		}

		// --------------------------
		// Support, Conoy, Hold orders

		// Nothing to do..

		$this->save();
		$nextTurn->save();

		$this->setStatus('complete');

		// Move turn pointer to next turn
		$this->getMatch()->next();

		return new ResolutionResult;
	}

	/**
	 * Get the state for territory $t in the next turn
	 *
	 * return State
	 **/
	public function getTerritoryNextState(TerritoryTemplate $t) {
		$nextState = StateQuery::create()
			->filterByMatch($this->getMatch())
			->filterByTurn($this->getMatch()->getNextTurn())
			->filterByTerritory($t)
			->findOne();
		return $nextState;
	}

	/**
	 * Initialize the next turn.  Create a new turn, point this match
	 * to it, and copy over the CURRENT state
	 */
	public function initiateNextTurn() {
		global $config;
//$config->system->db->useDebug(true);
		$nextTurn = Turn::create($this->getMatch(), $this);
		$sql = "INSERT INTO match_state "
			. " (match_id, turn_id, territory_id, occupier_id, unit_id) "
			." SELECT :match_id_static, :next_turn_id, territory_id, occupier_id, unit_id "
			."  FROM match_state "
			." WHERE match_id = :match_id AND turn_id = :current_turn_id ";
		$stmt = $config->system->db->prepare($sql);
		$stmt->execute(array(
			':match_id_static' => $this->getMatch()->getPrimaryKey(),
			':next_turn_id'    => $nextTurn->getPrimaryKey(),
			':match_id'        => $this->getMatch()->getPrimaryKey(),
			':current_turn_id' => $this->getPrimaryKey(),
		));

		$nextTurn->reload();
		$this->getMatch()->setNextTurn($nextTurn);
		return $nextTurn;
	}

	public function printOrders() {
		$str = "Orders:\n";
		$orders = $this->getOrders();
		foreach ($orders as $o) {
			$o = Order::downCast($o);
			$str .= str_pad($o, 40) . ($o->failed()?'FAIL':'PASS') . "\n";
			if ($o->failed()) {
				$transcript = preg_split("/\n/", trim($o->getTranscript()));
				foreach ($transcript as $t) {
					$str .= " - $t\n";
				}
			}
		}
		print $str."\n";
	}


}

/**
 * Small data structure to contain and export the required retreats data
 */
class ResolutionResult extends \ArrayObject {
	const RETREATS_REQUIRED = 1;
	const SUCCESS = 2;
	const UNINIT = -1;

	protected $required_retreats;
	protected $retreats;

	public function __construct() {
		$this->required_retreats = array();
		$this->retreats = array();
	}

	public function count() {
		return count($this->required_retreats);
	}
	public function append($val) {
		parent::append($val);
	}

	public function addRequiredRetreat(TerritoryTemplate $terr, Empire $loser, Empire $winner) {
		$this->required_retreats[] = array('territory' => $terr, 'winner' => $winner, 'loser' => $loser);
	}

	public function addRetreat(Retreat $o) {
		$this->retreats[] = $o;
	}

	/**
	 * Check to see if the required retreats have been satisfied.  If so,
	 * remove it from the required list.
	 */
	public function resolveRetreats() {
		$missing_retreats = array();
		foreach ($this->required_retreats as $rr) {
			if (!$this->findRequiredRetreatPair($rr)) {
				$missing_retreats[] = $rr;
			}
		}
		$this->required_retreats = $missing_retreats;
	}

	/**
	 * Loop through the retreat orders, if we have one that satisfies
	 * a required retreat, then return true.
	 */
	protected function findRequiredRetreatPair($rr) {
		foreach ($this->retreats as $retreat) {
			if ($rr['territory'] == $retreat->getSource()->getTerritory()
				  && $rr['loser'] == $retreat->getEmpire()) {
				// Got a match!
print "$retreat satisfies the required retreat from {$rr['territory']} by {$rr['loser']}\n";
				return true;
			}
		}
		return false;
	}

	/**
	 * Export to a simple array for the purposes of easily
	 * serializing the structure into JSON
	 */
	public function __toArray() {
		$ret = array(
			'code' => $this->status,
			'status' => $this->statusString(),
			'requiredRetreats' => array(), // figure out structure later
		);
	}

	public function getStatus() {
		if (count($this->required_retreats))
			return self::RETREATS_REQUIRED;
		else
			return self::SUCCESS;
	}

	/**
	 * Getter for the status, but returns a string
	 * (the status name)
	 */
	public function statusString() {
		switch ($this->getStatus()) {
		case self::RETREATS_REQUIRED:
			return 'Retreats Required';
		case self::SUCCESS:
			return 'Orders resolved';
		default:
			return 'Uninitialized';
		}
	}

	/**
	 * Mostly a debug function.
	 */
	public function __toString() {
		$str = '';
		$str = 'Status: ' . $this->statusString() . "\n";
		foreach ($this->required_retreats as $arr) {
			$str .= "{$arr['territory']} {$arr['loser']} must retreat due to {$arr['winner']}'s victory.\n";
		}
		$str .= "\n";
		return $str;
	}
}

/**
 * Little helper class to add up the 'winners' on a territory during order
 * resolution.  Implementing stuff here, instead of a bunch of array code
 * in the resolution function
 */
class PlayerMap {
	protected $map;
	protected $winner;
	protected $original_occupier;
	public function __construct(Empire $default = null) {
		$this->map = array();

		if (!is_null($default)) {
//print "Adding default point to $default\n";
			$this->original_occupier = $default;
			$this->inc($default); // add starting 'defenders' point
			$this->winner = $default; // Set as default winner
		} else {
			$this->winner = null;
		}
	}
	public function inc(Empire $empire, Order $order = null) {
		if (!array_key_exists($empire->getEmpireId(), $this->map))
			$this->map[$empire->getEmpireId()] = array('tally' => 0, 'empire' => $empire, 'orders' => array(), 'lost' => false);

//print "Incrementing $empire\n";
		$this->map[$empire->getEmpireId()]['tally']++;
		if (!is_null($order))
			$this->map[$empire->getEmpireId()]['orders'][$order->getPrimaryKey()]=$order;
	}
	public function findWinner() {
		// Iterate through the tallys, and see if any armies have tied.  Ties
		// result in standoffs.  The one exception here is that ties with the
		// current occupier yeild the current as the winner (or, aren't marked as
		// losers here.)
		foreach ($this->map as $empire_1_id=>&$arr1) {
			foreach ($this->map as $empire_2_id=>&$arr2) {
				if ($empire_1_id == $empire_2_id) continue;
				if ($arr1['tally'] == $arr2['tally']) {
					// We have a tie!
					if ($empire_1_id == $this->original_occupier->getPrimaryKey()) {
						foreach ($arr2['orders'] as $o) { $o->fail("Lost in tie to current occupier $this->original_occupier (e1)"); $o->save(); }
						$arr2['lost'] = true;
					} elseif ($empire_2_id == $this->original_occupier->getPrimaryKey()) {
						foreach ($arr1['orders'] as $o) { $o->fail("Lost in tie to current occupier $this->original_occupier (e2)"); $o->save(); }
						$arr1['lost'] = true;
					} else {
						foreach ($arr1['orders'] as $o) { $o->fail("Lost in stalemate to ". $arr2['empire'] . " (e3)"); $o->save(); }
						foreach ($arr2['orders'] as $o) { $o->fail("Lost in stalemate to ". $arr1['empire'] . " (e4)"); $o->save(); }
						$arr1['lost'] = true;
						$arr2['lost'] = true;
					}
				}
			}
		}

		// Now, find the winner.  Ignore any armies already marked as losers
		$c = -1;
		foreach ($this->map as $empire_id=>$arr) {
			if ($arr['lost'] !== true && $arr['tally'] > $c) {
//print "{$arr['empire']}={$arr['tally']} > $c\n";
				$c = $arr['tally'];
				$this->winner = $arr['empire'];
			}
		}
		return $this;
	}
	public function winner() {
		return $this->winner;
	}
	public function __toString() {
		$str = '';
		foreach ($this->map as $arr) {
			$str .= str_pad($arr['empire'], 12) . ' ' . sprintf('%0.2d', $arr['tally']) . ($arr['empire'] == $this->winner ? ' (winner)':'') . "\n";
		}
		return $str;
	}
}

class TurnException extends \Exception {};
class InvalidStateToExecute extends TurnException {}; // append Exception
class TurnClosedToOrdersException extends TurnException {};
class TurnNotCompleteException extends TurnException {};

// vim: ts=3 sw=3 noet :
