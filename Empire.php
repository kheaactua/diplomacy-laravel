<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Empire as BaseEmpire;
use DiplomacyEngine\iEmpire;

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
class Empire extends BaseEmpire implements iEmpire {

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


}

// vim: ts=3 sw=3 noet :
