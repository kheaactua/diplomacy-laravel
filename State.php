<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\State as BaseState;
use DiplomacyEngine\Territories\iTerritory;
use DiplomacyEngine\Empires\Unit;
use DiplomacyEngine\Empires\iEmpire;

/**
 * Skeleton subclass for representing a row from the 'match_state' table.
 *
 * Contains the match state for every turn
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class State extends BaseState {
	/**
	 * Creates a new match/turn state
	 *
	 * @return State
	 */
	public static function create(Game $game, Match $match, Turn $turn, TerritoryTemplate $territory, Empire $occupier, Unit $unit) {
		$o = new State;
		$o->setMatch($match);
		$o->setTurn($turn);
		$o->setTerritoryTemplate($territory);
		$o->setEmpire($occupier);
		$o->setUnit($unit);

		return $o;
	}

	public function __toString() {
		return sprintf("[Turn: %s] Territory %s occupied by %s's %f", $this->getTurn(), $this->getTerritoryTemplate(), $this->getEmpire(), $this->getUnit());
	}

	public function setOccupier(iEmpire $occupier, Unit $unit) {
		$this->setOccupier($occupier);
		$this->setUnit($unit);
	}

	public function getType() { return parent::getType(); }

}

// vim: ts=3 sw=3 noet :
