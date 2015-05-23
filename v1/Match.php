<?php

use Propel\Runtime\ActiveQuery\Criteria;

namespace DiplomacyEngineRestApi\v1;

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
