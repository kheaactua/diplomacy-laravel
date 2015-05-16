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
	protected $seasons;
	protected $seasons;
	/**
	 * New match.
	 * @param $name Name of the match/map
	 * @param $year Stating year, i.e. 1846
	 * @param $season Starting season (0=spring, 1=fall)
	 **/
	public static function create(Game $game, $name) {
		$m = new Match;
		$m->setName($name);
		// $m->year = $year;
		// $m->time = $season ? 1 : 0;

		// $m->seasons = array('spring', 'fall');

		// Create the first turn
		$turn = Turn::create($this);
		$m->currentTurn=$turn; // Pointer to current turn

		// Copy over the territories to our first state
		$tts = $game->getTerritoryTemplates();
		foreach ($tts as $tt) {
			$state = State::create($game, $this, $turn, $tt, $tt->getInitialOccupier(), $tt->getInitialUnit())
		}
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

	public function next() {
		$this->time++;
		$this->currentTurn = new Turn($this, $this->time%2);
		$this->turns[] = $this->currentTurn;
	}
	public function start() {
		$this->next();
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
		$str = "$this->name: ";
		$str .= $this->currentTurn()."\n";

		$state = $this->state();
		$str .= str_pad('Territory', 30) . str_pad('Empire', 12) . str_pad('Unit', 10) . "\n";
		$str .= str_pad('', 29, '-') . ' ' . str_pad('', 11, '-') . ' '. str_pad('', 10, '-') . "\n";
		foreach ($state as $s) {
			$str .= str_pad($s[0], 30) . str_pad($s[1], 12) . $s[0]->getUnit() . "\n";
		}
		return $str;
	}

}

// vim: ts=3 sw=3 noet :
