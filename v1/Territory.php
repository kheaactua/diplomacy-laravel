<?php

namespace DiplomacyEngineRestApi\v1;

use DiplomacyOrm\TerritoryTemplate;
use DiplomacyOrm\TerritoryTemplateQuery;

class Territory extends RouteHandler {

	/**
	 * Little getter similar to the one in Match
	 *
	 * Does not catch any exceptions
	 * @param int $territory_id
	 * @param intent(INOUT) Response& Response
	 * @return Match If no TerritoryTempalte was found, return null and fail the response appropriately
	 */
	protected function getTerritoryTemplate($territory_id, &$response) {
		$t = TerritoryTemplateQuery::create()->findPk($territory_id);
		if (!($t instanceof TerritoryTemplate)) {
			$resp->fail(Response::INVALID_MATCH, "Invalid territory $territory_id");
			return null;
		}
		return $t;
	}

	/**
	 * Get a list of all the matches in the DB.  Maybe later
	 * filter them by "active" or whatever.
	 *
	 * @param int $territory_id ID
	 * return array array('territory_name' =>, ..., 'neighbours' => array())
	 */
	public function doGetTerritory($territory_id) {
		$this->mlog->debug('['. __METHOD__ .']');
		$resp = new Response();

		$tt = $this->getTerritoryTemplate($territory_id, $resp);
		if (is_null($tt)) return $resp;

		$resp->data = array('territory' => $tt->__toArray(), 'neighbours' => array());
		$neighbours = $tt->getNeighbours();
		foreach ($neighbours as $n) {
			$resp->data['neighbours'][] = $n->__toArray();
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
	 * @param string $order_str Order text
	 * @return bool Whether the order was accepted
	**/
	public function doAddOrder($match_id, $empire_id, $order_str) {
		$this->mlog->debug('['. __METHOD__ .']');

		$resp = new Response();

		try {
			$order = $this->validateOrder($match_id, $empire_id, $order_str, $resp, $match, $empire);

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
	 * @param string $order_str Order we're creating/validating
	 * @param Response& $resp (intent(INOUT)) Response for this request
	 * @param Match& $match (intent(OUT)) Will populate the match variable
	 * @param Empire& $empire (intent(OUT)) Will populate the empire variable
	 * @return Order
	 * @throws Exception Passes along any exception it hits
	 */
	protected function validateOrder($match_id, $empire_id, $order_str, Response &$resp, &$match, &$empire) {
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
			$order = Order::interpretText($order_str, $match, $empire); // Order is not yet saved
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
	 * @param string $order_str Order text
	 * @return bool Whether the order was accepted
	**/
	public function doValidate($match_id, $empire_id, $order_str) {
		$this->mlog->debug('['. __METHOD__ .']');

		$resp = new Response();

		try {
			$order = $this->validateOrder($match_id, $empire_id, $order_str, $resp, $match, $empire);

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
	 * @return array array(TerritoryTemplate, ...)
	 */
	public function doGetEmpireTerritoryMap($match_id, $empire_id) {
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
			$territories[] = array(
				'territory' => $state->getTerritory()->__toArray(),
				'unit' => $state->getUnit(),
			);
		}
		$resp->data = $territories;

		return $resp->__toArray();
	}

}

// vim: sw=3 sts=3 ts=3 noet :
