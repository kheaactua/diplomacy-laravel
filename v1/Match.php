<?php

namespace DiplomacyEngineRestApi\v1;

use DiplomacyOrm\Match as MatchOrm;
use DiplomacyOrm\MatchQuery;
use DiplomacyOrm\Empire;
use DiplomacyOrm\EmpireQuery;
use DiplomacyOrm\Order;
use DiplomacyOrm\TurnException;
use DiplomacyOrm\OrderException;
use DiplomacyOrm\InvalidOrderException;

class Match extends RouteHandler {

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
			$match = MatchQuery::create()->findPk($match_id);
			if ($match instanceof MatchOrm) {
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
			} else {
				$resp->fail(Response::INVALID_MATCH, "Invalid match $match_id");
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
			do {
				$match = MatchQuery::create()->findPk($match_id);
				if (!($match instanceof Match)) {
					$resp->fail(Response::INVALID_MATCH, "Invalid match $match_id");
					break;
				}
				$result = $turn->processOrders();
				$resp->data = $result;
			} while (false);
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
			do {
				$match = MatchQuery::create()->findPk($match_id);
				if (!($match instanceof MatchOrm)) {
					$resp->fail(Response::INVALID_MATCH, "Invalid match $match_id");
					break;
				}
				$empire = EmpireQuery::create()
					->filterByGame($match->getGame())
					->filterByPrimaryKey($empire_id)
					->findOne();
				if (!($empire instanceof Empire)) {
					$resp->fail(Response::INVALID_EMPIRE, "Invalid empire $empire_id");
					break;
				}
				$order = Order::interpretText($order_str, $match, $empire);
				$match->getCurrentTurn()->addOrder($order);
				$resp->msg = "Order <<<$order>>> added";
				$resp->data=true;
			} while (false);
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
			do {
				$match = MatchQuery::create()->findPk($match_id);
				if (!($match instanceof MatchOrm)) {
					$resp->fail(Response::INVALID_MATCH, "Invalid match $match_id");
					break;
				}
				$empire = MatchQuery::create()
					->filterByGame($match->getPrimaryKey())
					->filterByPrimaryKey($empire_id)
					->findOne();
				if (!($empire instanceof Empire)) {
					$resp->fail(Response::INVALID_EMPIRE, "Invalid empire $match_id");
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
		} catch (Exception $ex) {
			$this->log->error('['. __METHOD__ .'] Caught exception: '. $e->getMessage());
			$resp->fail(Response::UNKNONWN_EXCEPTION, 'An error occured, please try again later');
		}

		return $resp->__toArray();
	}

}

// vim: sw=3 sts=3 ts=3 noet :
