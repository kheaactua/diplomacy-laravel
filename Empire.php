<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Empire as BaseEmpire;
use DiplomacyOrm\Map\StateTableMap;

/**
 * Skeleton subclass for representing a row from the 'empire' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Empire extends BaseEmpire {

	public static function create(Game $game, $abbr, $name_official, $name_long, $name_short) {
		$o = new Empire;
		$o->setGame($game);
		$o->setAbbr($abbr);
		$o->setName($name_official);
		$o->setNameLong($name_long);
		$o->setNameShort($name_short);
		$o->save();
		return $o;
	}

	public function __toString() {
		return $this->getNameShort();
	}

	public function getId() {
		return $this->getId();
	}

	/**
	 * Primarily used for outputting to JSON, rather than
	 * a data structure that'll be pushed in and out of the DB
	 *
	 * @return array array('empire_id', 'name_official', ...);
	 */
	public function __toArray() {
		return array(
			'empire_id' => $this->getPrimaryKey(),
			'abbr' => $this->getAbbr(),
			'name' => $this->getName(),
			'name_long' => $this->getNameLong(),
			'name_short' => $this->getNameShort(),
		);
	}
}


/**
 * Tiny class for unit type.  This is useful for storing it as a number (the
 * way I used to), but also for writing it as a string, moving it around,
 * comparing it, etc.
 *
 * That said, the only real advantage it has over a string now is type
 * checking, and easily importing data from the spreadsheet in.
 */
class Unit {
	protected $type;
	public function __construct($type = null) {
		if (strtolower($type) == 'a' || trim(strtolower($type)) == 'army' || $type == StateTableMap::COL_UNIT_ARMY)
			$this->type = StateTableMap::COL_UNIT_ARMY;
		elseif (strtolower($type) == 'f' || trim(strtolower($type)) == 'fleet' || $type == StateTableMap::COL_UNIT_FLEET)
			$this->type = StateTableMap::COL_UNIT_FLEET;
		elseif (strtolower($type) == 'v' || trim(strtolower($type)) == 'vacant' || $type == StateTableMap::COL_UNIT_VACANT)
			$this->type = StateTableMap::COL_UNIT_VACANT;
		elseif (is_null($type) || $type == StateTableMap::COL_UNIT_NONE)
			$this->type = StateTableMap::COL_UNIT_NONE;
		else {
			$this->type = StateTableMap::COL_UNIT_NONE;
			throw new InvalidUnitException('Must specify unit type, "'. $type .'" provided.');
		}
	}
	public function __toString() {
		switch ($this->type) {
			case StateTableMap::COL_UNIT_ARMY:
				return "Army";
			case StateTableMap::COL_UNIT_FLEET:
				return "Fleet";
			case StateTableMap::COL_UNIT_VACANT:
				return "Vacant";
			case StateTableMap::COL_UNIT_NONE:
				return "n/a";
			default:
				return "n/a";
		}
	}

	/**
	 * Used to give the value to the propel enum
	 */
	public function enum() {
		return $this->type;
	}
	public function isValid() {
		return $this->type != StateTableMap::COL_UNIT_NONE;
	}
}

class EmpireException extends \Exception { };
class InvalidUnitException extends EmpireException { };

// vim: ts=3 sw=3 noet :
