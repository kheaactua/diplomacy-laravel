<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\State as BaseState;
use DiplomacyEngine\iTerritory;
use DiplomacyEngine\Unit;
use DiplomacyEngine\iEmpire;

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

	public function setOccupation(iEmpire $occupier = null, $unit = null) {
		$this->setOccupier($occupier);
		$this->setUnit($unit);
	}

	public function setUnit($v) {
		if (is_null($v))
			$unit_str = 'none';
		elseif ($v instanceof Unit)
			$unit_str = $v->enum();
		elseif (is_string($v))
			$unit_str = $v;
		else
			trigger_error('Do not understand input to unit.');

		parent::setUnit($unit_str);
	}

	/**
	 * Returns true if an empire occupies the territory, easy
	 * (and more consistant) than testing if occupier is null
	 * or is an object, or isa something..
	 *
	 * @return bool
	 */
	public function isOccupied() {
		return !is_null($this->getOccupier());
	}

	public function getType() { return parent::getType(); }

}

// vim: ts=3 sw=3 noet :
