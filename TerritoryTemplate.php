<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\TerritoryTemplate as BaseTerritoryTemplate;
use DiplomacyOrm\Map\TerritoryTemplateTableMap;
use DiplomacyEngine\Territories\iTerritory;
use DiplomacyEngine\Empires\Unit;
use DiplomacyEngine\Empires\iEmpire;

/** Terrotory type, should use propel constants.
 * @deprecated */
define('TERR_LAND', 1);
define('TERR_WATER', 2);

/**
 * Skeleton subclass for representing a row from the 'territory_template' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class TerritoryTemplate extends BaseTerritoryTemplate {
	/**
	 * Creates a new Territory
	 *
	 * @return iTerritory
	 */
	public static function create($name, $type = TERR_LAND, $isSupply = false) {
		$t = new TerritoryTemplate;
		$t->setName($name);
		$t->setType($type);

		$t->setIsSupply($isSupply);

		return $t;
	}

	public function __toString() {
		return sprintf('[%1s] %s', $this->isLand()?'L':'W', $this->getName());
	}

	/** @return bool Check if $this is land */
	public function isLand() { return $this->type == TerritoryTemplateTableMap::COL_TYPE_LAND; }

	/** @return bool Check if $this is water */
	public function isWater() { return $this->type == TerritoryTemplateTableMap::COL_TYPE_WATER; }

	/**
	 * Old system used constants, while propel uses enums, so overloading
	 * this for backwards compatibility
	 *
	 * Maybe I should use COL_TYPE_LAND and COL_TYPE_WATER instead of magic
	 * strings
	 **/
	public function setType($type) {
		if ($type == TERR_LAND) {
			parent::setType('land');
		} elseif ($type == TERR_WATER) {
			parent::setType('water');
		} elseif (strtolower($type) === 'land' || strtolower($type) === 'water') {
			parent::setType($type);
		} else {
			trigger_error("Attempted to set invalid territory type: $type");
		}
	}
	public function getType() { return parent::getType(); }

	public function isNeighbour(iTerritory $neighbour) {
		return array_key_exists($neighbour->getId(), $this->neighbours);
	}

	/**
	 * Shortcut function, empire and unit always have to be set together,
	 * so this function saves me from having to do it twice all the time
	 */
	public function setInitialOccupation(iEmpire $empire, Unit $unit) {
		parent::setInitialOccupier($empire);
		parent::setInitialUnit($unit->enum());
	}

}

// vim: ts=3 sw=3 noet :
