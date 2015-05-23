<?php

use Propel\Runtime\ActiveQuery\Criteria;

namespace DiplomacyEngineRestApi\v1;

class Game extends RouteHandler {
	protected $orderConf;

	public function __construct() {
		parent::__construct();

	}

	/**
	 * Lists all the available games to start
	 *
	 * @param array $putData User data an an array
	 * @return array Array of game IDs and titles
	**/
	public function doGetGames() {
		$this->mlog->debug('['. __METHOD__ .']');

		$resp = new Response();

		$games = GameQuery::find();
		foreach ($games as $g) {
			$resp->data[] = array('game_id' => $g->getPrimaryKey(), $g->getName());
		}

		return $resp->__toArray();
	}

}

// vim: sw=3 sts=3 ts=3 noet :
