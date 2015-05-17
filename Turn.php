<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Turn as BaseTurn;
use DiplomacyEngine\Turns\iTurn;

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
class Turn extends BaseTurn implements iTurn {
	const dt = 2; // Timesteps per year.  Half of this is hard programmed in (Seasons)

	// /** Array representing the territories (but not Territory objects) used
	//  * to help resolve the fate of the Territory objects. */
	// protected $territories;

	/**
	 * Constructs a new turn.
	 *
	 * @param iMatch The match being played
	 * @param iTurn Last turn in match, null if this is the first turn
	 **/
	public static function create(Match $match, iTurn $last_turn = null) {
		$o = new Turn;
		$o->setMatch($match);

		if ($last_turn instanceof iTurn) {
			$o->setStep($last_turn->step+1);
		}
		$o->save();
		return $o;
	}

	public function isSpring() {
		return $this->getMatch()->getGame()->getStartSeason() % self::dt == 0;
	}

	public function __toString() {
		$season = $this->isSpring() ? 'Spring' : 'Fall';
		$year = $this->getMatch()->getGame()->getStartYear() + floor(($this->getStep()/self::dt));

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

	public function resolveAttacks() {
		$this->validateOrders();
		$this->removeOrdersFromAttackedTerritories();
		$this->resolveOrders();
		$this->carryOutOrders();
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
		foreach ($orders as &$o) {
			$o->validate();

			if ($o->failed()) continue;
			if (!array_key_exists($o->source->getTerritoryId(), $sources))
				$sources[$o->source->getTerritoryId()] = array();

// TODO make exception for CONVOYS
			$sources[$o->source->getTerritoryId()][] = $o;
		}
		foreach ($sources as $orders) {
			if (count($orders) > 1) {
				foreach ($orders as &$o) {
					$o->fail("Order conflicts with '". join("', '", $orders) . "'");
				}
			}
		}
	}

	/** Iterates through the list of orders, and removes any order
	 * who's source territory is the attack destination of another
	 * empire. */
	protected function removeOrdersFromAttackedTerritories() {
		foreach ($this->orders as &$order) {
			if ($order->failed()) continue;
			foreach ($this->orders as $ref) {
				if ($ref->failed()) continue;
				if ($order == $ref) continue;

				if (
					$order->source == $ref->dest
					&& $order->supporting() != $ref->supporting
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
	 * return array(iTerritory)
	 */
	protected function getActiveTerritories() {
		$ret = array();
		foreach ($this->orders as $o) {
			$ts = $o->territories();
			$ret = array_merge($ret, $ts);
		}
		return $ret;
	}

	/**
	 * Build an array of territories and which orders are acting upon them
	 * @return array(tid=>array('territory' => iTerritory, 'orders' => array(Orders), ..)
	 */
	protected function getTerritoryOrderMap($include_failed = true) {
		$ret = array();

		// Could make these loops more efficient, but doing it in the
		// "easiest to read" way.
		// First filter the territories down
		$ters = $this->getActiveTerritories();

		foreach ($ters as &$t) {
			$ret[$t->getTerritoryId()] = array('territory' => $t, 'orders' => array(), 'tally' => new PlayerMap($t->getOccupier()));

			foreach ($this->orders as &$o) {
				$affected = $o->territories();
				foreach ($affected as $t2) {
					if ($t2 == $t) {
						if (!$include_failed && $o->failed()) continue;
						$ret[$t->getTerritoryId()]['orders'][] = $o;
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
		$ters = $this->getTerritoryOrderMap(false); // false to skip failed orders
		foreach ($ters as $t_id=>&$map) {
			$t     = $map['territory'];
			$tally = $map['tally'];

			foreach ($map['orders'] as &$o) {
				if ($o->failed()) continue;

				if ($o instanceof Orders\Move) {
					if ($o->dest == $t) {
						$tally->inc($o->getEmpire());
					}
				} elseif ($o instanceof Orders\Support) {
					if ($o->dest == $t) {
						$tally->inc($o->supporting());
					}
				} else {
					trigger_error("Not sure how to perform [". get_class($o). "] $o");
				}
			}
		}

		foreach ($ters as $t_id=>&$map) {
			// Find the winner of each territory
			$t = $map['territory'];
			$winner = $map['tally']->findWinner()->winner();

			if (is_null($winner)) {
				continue;
			}

			// Loop through the orders again, fail all orders
			// who supported a loser, or lost a battle

			foreach ($map['orders'] as &$o) {
				if ($o->failed()) continue;
				if ($o instanceof Orders\Move) {
					if ($o->dest == $t && $o->getEmpire() != $winner) {
						$o->fail("Lost battle for $t to $winner");
					}
				} elseif ($o instanceof Orders\Support) {
					if ($o->dest == $t && $o->supporting() != $winner) {
						$o->fail("Supported ". $o->supporting() . " in failed campaign against $t that $winner won");
					}
				} else {
					trigger_error("Not sure how to perform [". get_class($o) . "]$o");
				}
			}
		}

		// Debug
		print "\n";
		print "Resolutions before retreats:\n";
		foreach ($ters as $t_id=>&$map) {
			print "{$map['territory']}, tally:\n";
			print $map['tally'];
			print "\n";
		}

		// Determine required retreats.  Go through the resolutions, and find all
		// occupiers who are not the winners
		$retreats = array();
		foreach ($ters as $t_id=>&$map) {
			$t = $map['territory'];
			$winner = $map['tally']->winner();

			if (is_null($winner)) continue;

			if ($t->getOccupier() != $winner) {
				$retreats[] = array('territory' => $t, 'empire' => $t->getOccupier(), 'winner' => $winner);
			}
		}

		// Debug, move to a function later or something.
		if (count($retreats)) {
			print "\nRequired Retreats!\n";
			foreach ($retreats as $arr) {
				print "{$arr['territory']} {$arr['empire']} must retreat due to $winner's victory.\n";
			}
		} else {
			print "No retreats required.\n";
		}
		print "\n";

	}

	function resolveRetreats() {

	}


	function carryOutOrders() {

	}

	public function printOrders() {
		$str = "Orders:\n";
		foreach ($this->orders as $o) {
			$str .= str_pad($o, 40) . ($o->failed()?'FAIL':'PASS') . "\n";
			if ($o->failed()) {
				$transcript = $o->getTranscript();
				foreach ($transcript as $t) {
					$str .= " - $t\n";
				}
			}
		}
		print $str."\n";
	}


}

// vim: ts=3 sw=3 noet :
