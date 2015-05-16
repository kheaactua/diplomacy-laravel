<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Empire as BaseEmpire;
use DiplomacyEngine\Empires\iEmpire;

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

	public function __construct($id, $name_official, $name_long, $name_short) {
		parent::__construct();
		$this->setEmpireId($id);
		$this->setNameOfficial($name_official);
		$this->setNameLong($name_long);
		$this->setNameShort($name_short);
	}

	public function __toString() {
		return $this->getNameShort();
	}

	public function getId() {
		return $this->getId();
	}

	/**
	 * Reads in a JSON object with all the empires defined.
	 * [{id: 'CAN', name_official: 'Canada', name_long: 'Dominion of Canada', name_short: 'Canada' }, ...]
	 */
	public static function loadEmpires(array $objs) {
		//print_r($objs);
		$empires = array();
		foreach ($objs as $obj) {
			$t = new Empire($obj->id, $obj->name_official, $obj->name_long, $obj->name_short);
			$empires[$t->getEmpireId()] = $t;
		}
		return $empires;
	}

}

// vim: ts=3 sw=3 noet :
