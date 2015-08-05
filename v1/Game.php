<?php

namespace DiplomacyEngineRestApi\v1;

use Propel\Runtime\ActiveQuery\Criteria;
use DiplomacyOrm\GameQuery;

class Game extends RouteHandler {
	/**
	 * Lists all the available games to start
	 *
	 * @return array Array of game IDs and titles
	**/
	public function doGetGames() {
		$this->mlog->debug('['. __METHOD__ .']');

		$resp = new Response();

		$games = GameQuery::create()->find();
		foreach ($games as $g) {
			$resp->data[] = array('game_id' => $g->getPrimaryKey(), $g->getName());
		}

		return $resp->__toArray();
	}

}

// vim: sw=3 sts=3 ts=3 noet :
