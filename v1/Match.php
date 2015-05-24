<?php

namespace DiplomacyEngineRestApi\v1;

use DiplomacyOrm\Match as MatchOrm;
use DiplomacyOrm\MatchQuery;

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

}

// vim: sw=3 sts=3 ts=3 noet :
