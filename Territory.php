<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Territory as BaseTerritory;
use DiplomacyEngine\Territories\iTerritory;
use DiplomacyEngine\Empires\Unit;
use DiplomacyEngine\Empires\iEmpire;

//use DiplomacyEngine\Territory\Neighbours;

/**
 * Skeleton subclass for representing a row from the 'territory' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Territory extends BaseTerritory implements iTerritory {
	protected $state;
	protected $occupier;
	protected $unit;
	protected $neighbours;

	/**
	 * Creates a new Territory
	 *
	 * @return iTerritory
	 */
	public static function create($id, $name, $type = TERR_LAND, $isSupply = false) {
		$t = new Territory;
		$t->setTerritoryId($id);
		$t->setName($name);
		$t->setType($type);

		$t->setIsSupply($isSupply);

		//$t->neighbours = new Neighbours();
		$t->state = null;
		$t->occupier = null;
		$t->unit = null;

		return $t;
	}

	public function __toString() {
		return $this->name;
	}

	/**
	 * Load this territory with the current game state.  From this
	 * it can determine it's occupier, the game, turn, match, etc.
	 */
	public function setState(Match $match, Turn $turn) {
		$this->state = StateQuery::findPk($match->getGame(), $match, $this);
	}


	/**
	 * Who is occupying this territory
	 *
	 * @return iEmpire
	 */
	public function getOccupier() {
		if (is_null($this->state)) trigger_error("Territory state is not set.  Use ::setState()");

		return $this->state->getOccupier();
	}

	/**
	 * Type of occupying unit
	 *
	 * @return Unit
	 */
	public function getUnit() {
		if (is_null($this->state)) trigger_error("Territory state is not set.  Use ::setState()");

		return $this->state->getUnit();
	}

	public function setOccupier(iEmpire $occupier, Unit $unit) {
		if (is_null($this->state)) trigger_error("Territory state is not set.  Use ::setState()");

		$this->state->setOccupier($occupier);
		$this->state->setUnit($unit);
	}

	/** @return bool Check if $this is land */
	public function isLand() { return $this->type == TerritoryTableMap::COL_TYPE_LAND; }

	/** @return bool Check if $this is water */
	public function isWater() { return $this->type == TerritoryTableMap::COL_TYPE_WATER; }

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

	public function addNeighbour(iTerritory $neighbour) { trigger_error("Not implemented."); }
	public function addNeighbours(array $neighbours) { trigger_error("Not implemented."); }
	public function getNeighbours() { trigger_error("Not implemented."); }
	/*
	public function addNeighbour(iTerritory $neighbour) {
		if (!array_key_exists($neighbour->getId(), $this->neighbours)) {
			$this->neighbours[$neighbour->getId()] = $neighbour;

			// The check above should prevent this from being an infinite loop
			$neighbour->addNeighbour($this);
		}
	}
	public function addNeighbours(array $neighbours) {
		trigger_error('Not implemented');
	}
	public function getNeighbours() { return $this->neighbours; }
	*/
	public function isNeighbour(iTerritory $neighbour) {
		return array_key_exists($neighbour->getId(), $this->neighbours);
	}

	/** @return bool Has Supply center */
	public function getIsSupplyCenter() { return parent::getIsSupplyCenter(); }
	public function setIsSupplyCenter($hasSupply) { return parent::setIsSupplyCenter($hasSuppy); }

	/**
	 * Responsible for the initial loading of a territory into the game (not
	 * the match)
	 **/
	public static function loadTerritories(array $empires, array $objs) {
		$ts = array();
		foreach ($objs as $obj) {
			$t = self::create($obj->id, $obj->name, $obj->type, false); // TODO fix supply center

			if (array_key_exists($obj->empire_start, $empires)) {
				$t->setOccupier($empires[$obj->empire_start], new Unit($obj->starting_forces));
			};

			$ts[$t->getId()] = $t;
		}

		// Second pass, set up neighbours
		foreach ($objs as $obj) {
			$t = $ts[$obj->id];
			foreach ($obj->neighbours as $nid) {
				$n = $ts[$nid];
				$t->addNeighbour($n);
			}
		}
		return $ts;
	}

	/**
	 * Quick territory lookup.  Written for backwards compatibility
	 * with pre-ORM Diplomacy engine
	 *
	 * @return iTerritory
	 */
	public static function findTerritoryByName(Game $game, $name) {
		return TerritoryQuery::create()
			->filterByGame($game)
			->findTerritoryByName($name)
		;
	}

}

// vim: ts=3 sw=3 noet :
