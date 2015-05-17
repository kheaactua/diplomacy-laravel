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
	public static function create(Game $game, Match $match, Turn $turn, TerritoryTemplate $territory, Empire $occupier = null, Unit $unit = null) {
		$o = new State;
		$o->setMatch($match);
		$o->setTurn($turn);
		$o->setTerritory($territory);
		if (!is_null($occupier)) {
			$o->setOccupation($occupier, $unit);
		}
		$o->save();

		return $o;
	}

	public function __toString() {
		if (is_null($this->getOccupier()))
			return sprintf("[Turn: %s] Territory %s unoccupied", $this->getTurn(), $this->getTerritory());
		else
			return sprintf("[Turn: %s] Territory %s occupied by %s's %s", $this->getTurn(), $this->getTerritory(), $this->getOccupier(), $this->getUnit());
	}

	public function setOccupation(iEmpire $occupier, Unit $unit) {
		$this->setOccupier($occupier);
		$this->setUnit($unit->enum());
	}

	public function getType() { return parent::getType(); }

}

// vim: ts=3 sw=3 noet :
