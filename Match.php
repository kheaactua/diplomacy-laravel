<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Match as BaseMatch;

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
		$m->setCurrentTurn($turn);

		// Copy over the territories to our first state
		$tts = $game->getGameTerritories();
		foreach ($tts as $tt) {
			$state = State::create($game, $m, $turn, $tt, $tt->getInitialOccupier(), new Unit($tt->getInitialUnit()));
		}
		$m->save();
		return $m;
	}

	/**
	 * Create a new turn and point currentTurn at it
	 */
	public function next() {
		if ($this->getCurrentTurn()->getStatus() != 'complete')
			throw new TurnNotCompleteException("Current turn currently has a status of '". $this->getCurrentTurn()->getStatus() . "', cannot proceed.");

		print "next turn: ". gettype($this->getNextTurn()) . "\n";
		if (!($this->getNextTurn() instanceof Turn))
			throw new TurnNotCompleteException("No next turn has been initialized");


		// else ...
		$this->setCurrentTurn($this->getNextTurn());
		$this->setNextTurn(null);
	}

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
		$str .= $this->getCurrentTurn() . "\n";

		$states = StateQuery::create()->filterByMatch($this)->filterByTurn($this->getCurrentTurn());

		$str .= str_pad('Territory', 30) . str_pad('Empire', 12) . str_pad('Unit', 10) . "\n";
		$str .= str_pad('', 29, '-') . ' ' . str_pad('', 11, '-') . ' '. str_pad('', 10, '-') . "\n";
		foreach ($states as $s) {
			$str .= str_pad($s->getTerritory(), 30) . str_pad($s->getOccupier(), 12) . new Unit($s->getUnit()) . "\n";
		}
		return $str;
	}

}

// vim: ts=3 sw=3 noet :
