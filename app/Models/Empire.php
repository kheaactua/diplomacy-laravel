<?php

namespace App\Models;

use App\Models\Base\Empire as BaseEmpire;
use App\Models\Map\StateTableMap;

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

class EmpireException extends \Exception { };
class InvalidUnitException extends EmpireException { };

// vim: ts=3 sw=3 noet :
