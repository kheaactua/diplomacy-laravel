<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Match as BaseMatch;
use DiplomacyEngine\Unit;

/**
 * Skeleton subclass for representing a row from the 'match' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Match extends BaseMatch {

	// Pointer to current turn
	protected $currentTurn;

	public function __construct() {
		if ($this->getMatchId()) {
			// If we're an object.. Set the currentTurn
			$c = new \Propel\Runtime\ActiveQuery\Criteria;
			$c->addDescendingOrderByColumn(TurnTableMap::COL_CREATED_ON);
			$turns = $this->getTurns(null, $c);
			$this->currentTurn = $turns[0];
		}
	}

	/**
	 * New match.
	 * @param $name Name of the match/map
	 * @param $year Stating year, i.e. 1846
	 * @param $season Starting season (0=spring, 1=fall)
	 **/
	public static function create(Game $game, $name) {
		$m = new Match;
		$m->setGame($game);
		$m->setName($name);

		// Create the first turn
		$turn = Turn::create($m);
		$m->currentTurn=$turn;

		// Copy over the territories to our first state
		$tts = $game->getGameTerritories();
		foreach ($tts as $tt) {
			$state = State::create($game, $m, $turn, $tt, $tt->getInitialOccupier(), new Unit($tt->getInitialUnit()));
		}
		$m->save();
		return $m;
	}

	/*
	 * These are for the game
	 *
	public function addEmpire(iEmpire $empire) { return parent::addEmpure($empire); }
	public function setEmpires(array $empires) {
		foreach ($empires as $e)
			$this->addEmpire($e);
	}
	 */

	/**
	 * Create a new turn and point currentTurn at it
	 */
	public function next() {
		$turn = Turn::create($this, $this->currentTurn);
		$m->currentTurn=$turn; // Pointer to current turn
	}

	public function getCurrentTurn() {
		return $this->currentTurn;
	}

	/**
	 * Another init function: Will copy the territories for a Game into the
	 * match state table
	 */
	/*
	public function setTerritories(array $territories) {
		$this->territories = $territories;
	}
	public function getTerritories() {
		return $this->territories;
	}
	*/

	/*
	public function state() {
		$state = array();
		foreach ($this->territories as $t) {
			$state[$t->getTerritoryId()] = array($t, $t->getOccupier());
		}
		return $state;
	}
	*/

	public function __toString() {
		$str = '';
		$str .= $this->getName().": ";
		$str .= $this->currentTurn . "\n";

		$states = StateQuery::create()->filterByMatch($this)->filterByTurn($this->currentTurn);

		$str .= str_pad('Territory', 30) . str_pad('Empire', 12) . str_pad('Unit', 10) . "\n";
		$str .= str_pad('', 29, '-') . ' ' . str_pad('', 11, '-') . ' '. str_pad('', 10, '-') . "\n";
		foreach ($states as $s) {
			$str .= str_pad($s->getTerritory(), 30) . str_pad($s->getOccupier(), 12) . new Unit($s->getUnit()) . "\n";
		}
		return $str;
	}

}

// vim: ts=3 sw=3 noet :
