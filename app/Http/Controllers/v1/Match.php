<?php

namespace DiplomacyEngineRestApi\v1;

use DiplomacyOrm\Match as MatchOrm;
use DiplomacyOrm\MatchQuery;
use DiplomacyOrm\State;
use DiplomacyOrm\StateQuery;
use DiplomacyOrm\Empire;
use DiplomacyOrm\EmpireQuery;
use DiplomacyOrm\Order;
use DiplomacyOrm\OrderException;
use DiplomacyOrm\ResolutionResult;
use DiplomacyOrm\InvalidOrderException;
use DiplomacyOrm\TurnException;

class Match extends RouteHandler {

	/**
	 * I find my self doing this in almost every function, so
	 * just get it done.
	 *
	 * Does not catch any exceptions
	 * @param int $match_id
	 * @param intent(INOUT) Response& Response
	 * @return Match If no match was found, return null and fail the response appropriately
	 */
	protected function getMatch($match_id, Response &$resp) {
		$match = MatchQuery::create()->findPk($match_id);
		if (!($match instanceof MatchOrm)) {
			$resp->fail(Response::INVALID_MATCH, "Invalid match $match_id");
			return null;
		}
		return $match;
	}

	/**
	 * Getter for empire
	 *
	 * Does not catch any exceptions
	 * @param int $empire_id
	 * @param Match $match
	 * @param intent(INOUT) Response& Response
	 * @return Match If no match was found, return null and fail the response appropriately
	 */
	protected function getEmpire($empire_id, $match, Response &$resp) {
		$empire = EmpireQuery::create()->findPk($empire_id);
		if (!($empire instanceof Empire)) {
			$resp->fail(Response::INVALID_MATCH, "Invalid empire $empire_id");
			return null;
		}
		return $empire;
	}

	/**
	 * Get a list of all the matches in the DB.  Maybe later
	 * filter them by "active" or whatever.
	 *
	 * return array array(array(match_id, match_name, created, last_updated, game_id))
	 */
	public function doGetMatches() {
		$this->mlog->debug('['. __METHOD__ .']');
		$resp = new Response();
		$matches = MatchQuery::create()->find();
		$resp->data = array();
		foreach ($matches as $match) {
			$resp->data[] = $match->__toArray();
		}
		return $resp->__toArray();
	}

	/**
	 * Lists all the available games to start
	 *
	 * @param int $game_id ID of the game to start
	 * @param string Name of your match
	 * @return array The new match object
	**/
	public function doCreateMatch($game_id, $game_name) {
		$this->mlog->debug('['. __METHOD__ .']');

		$resp = new Response();

		try {
			$game = GameQuery::findPk($game_id);
			if ($game instanceof Game) {
				$match = Match::create($game, $game_name);
				$resp->data=$match->__toArray();
			}
		} catch (Exception $ex) {
			$resp->fail(Response::UNKNONWN_EXCEPTION, 'An error occured, please try again later');
		}

		return $resp->__toArray();
	}

	/**
	 * List the empires in the match.  This should be on the game, but
	 * it's used more in the context of the match.
	 *
	 * @param int $match_id Match with the empires
	 * @return array List of empire objects
	 */
	public function doGetEmpires($match_id) {
		$this->mlog->debug('['. __METHOD__ .']');

		$resp = new Response();

		try {
			$match = $this->getMatch($match_id, $resp);
			if (!is_null($match)) {
				$empires = $match->getGame()->getEmpires();
				$resp->data = array();
				foreach ($empires as $empire) {
					$resp->data[] = $empire->__toArray();
				}
				if (count($empires) == 0) {
					$resp->fail(Response::NO_EMPIRES, 'No empires matches your query.  This is probably indicative of a larger issue.');
				} else {
					$resp->msg = "Empires in ". $match->getName() ."";
				}
			}
		} catch (Exception $ex) {
			$resp->fail(Response::INVALID_MATCH, "Invalid match $match_id");
		}

		return $resp->__toArray();
	}

	/**
	 * Kicks off the resolution process
	 *
	 * @return array Result object from the resolution
	**/
	public function doResolve($game_id, $game_name) {
		$this->mlog->debug('['. __METHOD__ .']');

		$resp = new Response();

		try {
			$match = $this->getMatch($match_id, $resp);
			if (!is_null($match)) {
				$result = $turn->processOrders();
				if ($result->getStatus() == ResolutionResult::RETREATS_REQUIRED)
					$resp->msg  = "Retreats required";
				elseif ($result->getStatus() == ResolutionResult::SUCCESS)
					$resp->msg  = "Orders successfully executed.  Turn is now closed.";

				if ($result->retreatsRequired())
					$resp->code = Response::TURN_RETREATS_REQUIRED;
				elseif ($result->isSupplySeason())
					$resp->code = Response::TURN_SUPPLY_SEASON;

				$resp->data = $result;
			}
		} catch (Exception $ex) {
			$resp->fail(Response::UNKNONWN_EXCEPTION, 'An error occured, please try again later');
		}

		return $resp->__toArray();
	}

	/**
	 * Lists all the available games to start
	 *
	 * @param int $match_id Match ID
	 * @param int $empire_id Empire ID issuing the order
	 * @param string $orderStr Order text
	 * @return bool Whether the order was accepted
	**/
	public function doAddOrder($match_id, $empire_id, $orderStr) {
		$this->mlog->debug('['. __METHOD__ .']');

		$resp = new Response();

		try {
			$order = $this->validateOrder($match_id, $empire_id, $orderStr, $resp, $match, $empire);

			if ($order instanceof $order && !$order->failed()) {
				$match->getCurrentTurn()->addOrder($order);
				$resp->msg = "Order <<<$order>>> added";
				$resp->data=true;
			} else
				$resp->data=false;

		} catch (InvalidOrderException $ex) {
			$resp->fail(Response::INVALID_ORDER, $ex->getMessage());
		} catch (TurnException $ex) {
			$resp->fail(Response::TURN_ERROR, $ex->getMessage());
		} catch (Exception $ex) {
			$this->log->error('['. __METHOD__ .'] Caught exception: '. $e->getMessage());
			$resp->fail(Response::UNKNONWN_EXCEPTION, 'An error occured, please try again later');
		}

		return $resp->__toArray();
	}

	/**
	 * Main workhorse behind doAddOrder and doValidate
	 *
	 * @param int $match_id Match
	 * @param int $empire_id empire
	 * @param string $orderStr Order we're creating/validating
	 * @param Response& $resp (intent(INOUT)) Response for this request
	 * @param Match& $match (intent(OUT)) Will populate the match variable
	 * @param Empire& $empire (intent(OUT)) Will populate the empire variable
	 * @return Order
	 * @throws Exception Passes along any exception it hits
	 */
	protected function validateOrder($match_id, $empire_id, $orderStr, Response &$resp, &$match, &$empire) {
		$order = null;
		do {
			$match = $this->getMatch($match_id, $resp);
			if (is_null($match)) break;

			$empire = EmpireQuery::create()
				->filterByGame($match->getGame())
				->filterByPrimaryKey($empire_id)
				->findOne();
			if (!($empire instanceof Empire)) {
				$resp->fail(Response::INVALID_EMPIRE, "Invalid empire $empire_id");
				break;
			}
			$order = Order::interpretText($orderStr, $match, $empire); // Order is not yet saved
			$order->validate(false); // false for light validation
			if ($order->failed()) {
				$resp->fail(Response::INVALID_ORDER, $order->getTranscript());
				break;
			}
			$resp->data=true;
		} while (false);
		return $order;
	}


	/**
	 * Validates an order based on syntax and ownership.  Does not
	 * consider neighbouring territories, or what other players are
	 * doing.
	 *
	 * @param int $match_id Match ID
	 * @param int $empire_id Empire ID issuing the order
	 * @param string $orderStr Order text
	 * @return bool Whether the order was accepted
	**/
	public function doValidate($match_id, $empire_id, $orderStr) {
		$this->mlog->debug('['. __METHOD__ .']');

		$resp = new Response();

		try {
			$order = $this->validateOrder($match_id, $empire_id, $orderStr, $resp, $match, $empire);

			if ($order instanceof $order)
				$resp->data=!$order->failed();
			else
				$resp->data=false;

		} catch (Exception $ex) {
			$this->log->error('['. __METHOD__ .'] Caught exception: '. $e->getMessage());
			$resp->fail(Response::UNKNONWN_EXCEPTION, 'An error occured, please try again later');
		}

		return $resp->__toArray();
	}

	/**
	 * Fetch the list of territories owned by an empire.
	 *
	 * @param int $match_id Match ID
	 * @param int $empire_id Empire ID issuing the order
	 * @param bool $include_neighbours Include the terrotories neighbours in the result.
	 * @return array array(TerritoryTemplate, ...)
	 */
	public function doGetEmpireTerritoryMap($match_id, $empire_id, $include_neighbours=false) {
		$resp = new Response();
		$match = $this->getMatch($match_id, $resp);
		if (is_null($match)) return $resp;
		$empire = $this->getEmpire($empire_id, $match, $resp);
		if (is_null($empire)) return $resp;


		$states = StateQuery::create()
			->filterByTurn($match->getCurrentTurn())
			->filterByOccupier($empire)
			->find()
		;

		$territories = array();
		foreach ($states as $state) {
			$arr = array(
				'territory' => $state->getTerritory()->__toArray(),
				'unit' => is_object($state->getUnit())? $state->getUnit()->__toString() : 'none',
			);

			if ($include_neighbours) {
				$neighbours = $state->getTerritory()->getNeighbours();
				foreach ($neighbours as $n)
					$arr['neighbours'][] = $n->__toArray();
			}

			$territories[] = $arr;
		}
		$resp->data = $territories;

		return $resp->__toArray();
	}

	/**
	 * Fetch the list of territories owned by an empire.
	 *
	 * @param int $match_id Match ID
	 * @param bool $include_neighbours Include neighbours in the territory output.  This will substancially increase the size of the data
	 * @return array array(TerritoryTemplate, ...)
	 */
	public function doGetTerritories($match_id, $include_neighbours = false) {
		$resp = new Response();
		$match = $this->getMatch($match_id, $resp);
		if (is_null($match)) return $resp;

		$states = StateQuery::create()
			->filterByTurn($match->getCurrentTurn())
			->find()
		;

		$territories = array();
		foreach ($states as $state) {
			$arr = $state->getTerritory()->__toArray();
			if (!is_null($state->getOccupier())) {
				$arr['occupier'] = $state->getOccupier()->__toArray();
				$arr['unit'] = $state->getUnit();
			} else {
				$arr['occupier'] = '';
				$arr['unit'] = 'none';
			}

			// Don't need, but might be nice to have for debugging/fixing
			$arr['state_id'] = $state->getPrimaryKey();

			if ($include_neighbours) {
				$neighbours = $state->getTerritory()->getNeighbours();
				foreach ($neighbours as $n)
					$arr['neighbours'][] = $n->__toArray();
			}

			$territories[] = $arr;
		}
		$resp->data = $territories;

		return $resp->__toArray();
	}

}

// vim: sw=3 sts=3 ts=3 noet :
