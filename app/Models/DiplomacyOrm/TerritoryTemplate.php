<?php

namespace App\Models\DiplomacyOrm;

use App\Models\DiplomacyOrm\Base\TerritoryTemplate as BaseTerritoryTemplate;
use App\Models\DiplomacyOrm\Map\TerritoryTemplateTableMap;
use DiplomacyEngine\iTerritory;

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

	public function shortType() {
		if ($this->isLand()) return 'L';
		if ($this->isWater()) return 'W';
		if ($this->isCoast()) return 'C';
		return $this->getType();
	}

	public function __toString() {
		//return sprintf('(%1s)%s', $this->shortType(), $this->getName());
		return $this->getName();
	}

	/** @return bool Check if $this is land */
	public function isLand() { return $this->getType() == TerritoryTemplateTableMap::COL_TYPE_LAND; }

	/** @return bool Check if $this is a coast */
	public function isCoast() { return $this->getType() == TerritoryTemplateTableMap::COL_TYPE_COAST; }

	/** @return bool Check if $this is water */
	public function isWater() { return $this->getType() == TerritoryTemplateTableMap::COL_TYPE_WATER; }

	/**
	 * Old system used constants, while propel uses enums, so overloading
	 * this for backwards compatibility
	 *
	 * Maybe I should use COL_TYPE_LAND and COL_TYPE_WATER instead of magic
	 * strings.
	 *
	 * This is here to help input Google Spreadsheet values
	 *
	 **/
	public function setType($type) {
		$type = strtolower($type);
		if ($type == TerritoryTemplateTableMap::COL_TYPE_LAND || $type == TerritoryTemplateTableMap::COL_TYPE_WATER || $type == TerritoryTemplateTableMap::COL_TYPE_COAST) {
			parent::setType($type);
		} elseif ($type == TERR_LAND) {
			parent::setType(TerritoryTemplateTableMap::COL_TYPE_LAND);
		} elseif ($type == TERR_WATER) {
			parent::setType(TerritoryTemplateTableMap::COL_TYPE_WATER);
		} elseif ($type == TERR_COAST) {
			parent::setType(TerritoryTemplateTableMap::COL_TYPE_COAST);
		} else {
			trigger_error("Attempted to set invalid territory type: $type");
		}
	}
	public function isNeighbour(iTerritory $neighbour) {
		return array_key_exists($neighbour->getId(), $this->neighbours);
	}

	/**
	 * Shortcut function, empire and unit always have to be set together,
	 * so this function saves me from having to do it twice all the time
	 */
	public function setInitialOccupation(Empire $empire, Unit $unit) {
		parent::setInitialOccupier($empire);
		parent::setInitialUnit($unit->getUnitType());
	}

	/**
	 * Serialize the object into an array which can be converted to JSON easily
	 */
	public function __toArray() {
		return array(
			'territoryId' => $this->getPrimaryKey(),
			'name'         => $this->getName(),
			'type'         => $this->getType(),
			'isSupply'    => $this->getIsSupply()
		);
	}

}

// vim: ts=3 sw=3 noet :
