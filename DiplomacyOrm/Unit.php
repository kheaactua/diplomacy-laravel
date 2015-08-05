<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Map\StateTableMap;

/**
 * Tiny class for unit type.  This object is 'state'less, and is just used as a
 * convinient way to operate with the unit type strings, and parameter
 * checking.
 *
 * This object was original a little container class, then was mapped to a
 * class, and now has returned.  The benefit is that its interface is very
 * consistant with everything else.
 *
 * This object should be returned to lib, or somewhere out of the ORM
 */
class Unit {
	protected $type;

	public function __construct($type = null) {
		if (!is_null($type)) $this->setUnitType($type);
	}

	public function getUnitType() {
		return $this->type;
	}

	public function setUnitType($v) {
		$official_type = self::convert($v);
		if ($official_type === false) {
			throw new InvalidUnitException('Must specify unit type, "'. $type .'" provided.');
		}
		$this->type = $official_type;
	}

	public function __toString() {
		switch ($this->getUnitType()) {
			case StateTableMap::COL_UNIT_TYPE_FLEET:
				return "Fleet";
			case StateTableMap::COL_UNIT_TYPE_ARMY:
				return "Army";
			case StateTableMap::COL_UNIT_TYPE_VACANT:
				return "Vacant Unit";
			case StateTableMap::COL_UNIT_TYPE_NONE:
				return "n/a";
			default:
				return "n/a";
		}
	}

	/**
	 * Little function to return whether this guy is an active force, or if it's
	 * vacant or something */
	public function isForce() {
		return $this->getUnitType() == StateTableMap::COL_UNIT_TYPE_FLEET || $this->getUnitType() == StateTableMap::COL_UNIT_TYPE_ARMY;
	}

	/**
	 * Function designed to take broad inputs and output an official unit type
	*/
	public static function convert($type) {
		if (strtolower($type) == 'f' || trim(strtolower($type)) == 'fleet' || $type == StateTableMap::COL_UNIT_TYPE_FLEET)
			return StateTableMap::COL_UNIT_TYPE_FLEET;
		elseif (strtolower($type) == 'a' || trim(strtolower($type)) == 'army' || $type == StateTableMap::COL_UNIT_TYPE_ARMY)
			return StateTableMap::COL_UNIT_TYPE_ARMY;
		elseif (strtolower($type) == 'v' || trim(strtolower($type)) == 'vacant' || $type == StateTableMap::COL_UNIT_TYPE_VACANT)
			return StateTableMap::COL_UNIT_TYPE_VACANT;
		elseif (is_null($type) || $type == StateTableMap::COL_UNIT_TYPE_NONE)
			return StateTableMap::COL_UNIT_TYPE_NONE;
		else
			return false;
	}

	/**
	 * Used to give the value to the propel enum
	 */
	public function enum() {
		return $this->type;
	}

	/**
	 * Mostly a debug function.
	 * Print out all the units for a given turn.
	 */
	public static function printUnitTable(Turn $turn) {
		$states = StateQuery::create()
			->filterByTurn($turn)
			->find();

		$str = '';
		$str .= "State of units:\n";
		if (count($states) == 0) {
			$str .= "Warning! No units!\n";
			return $str;
		}
		$str .= str_pad('Empire', 12) .    str_pad('Unit', 7)      . str_pad('Territory', 30) ."\n";
		$str .= str_pad('', 11, '-') .' '. str_pad('', 6, '-') .' '. str_pad('', 29, '-')    ."\n";
		foreach ($states as $state) {
			$str .= str_pad($state->getOccupier(), 12);
			$str .= str_pad($state->getUnitType(), 7);

			if (is_object($state)) {
				$currentTerritory = $state->getTerritory()->__toString();
			} else {
				// Retreated..
				$currentTerritory = '<stateless>';
			}

			$str .= str_pad($currentTerritory, 30) . "\n";
		}
		return $str;
	}

}

// vim: ts=3 sw=3 noet :
