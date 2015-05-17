<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Game as BaseGame;
use DiplomacyEngine\iEmpire;
use DiplomacyEngine\Unit;

/**
 * Skeleton subclass for representing a row from the 'game' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Game extends BaseGame {
	public static function create($name, $year, $season) {
		$o = new Game;
		$o->setName($name);
		$o->setStartYear($year);
		$o->setStartSeason($season);
		$o->save();
		return $o;
	}

	/**
	 * Reads in a JSON object with all the empires defined.
	 * [{id: 'CAN', name_official: 'Canada', name_long: 'Dominion of Canada', name_short: 'Canada' }, ...]
	 */
	public function loadEmpires(array $objs) {
		//print_r($objs);
		//$empires = array();
		foreach ($objs as $obj) {
			$t = Empire::create($this, $obj->id, $obj->name_official, $obj->name_long, $obj->name_short);
			$this->addEmpire($t);
		}
		$this->save();
	}

	/**
	 * Responsible for the initial loading of a territory into the game (not
	 * the match)
	 **/
	public function loadTerritories(array $objs) {
		//print_r($objs);
		$ts = array();
		foreach ($objs as $obj) {
			$t = TerritoryTemplate::create($obj->name, $obj->type, false); // TODO fix supply center

			$empire = EmpireQuery::create()->filterByGame($this)->filterByAbbr($obj->empire_start)->findOne();
			if ($empire instanceof iEmpire) {
				$t->setInitialOccupation($empire, new Unit($obj->starting_forces));
			}

			$this->addGameTerritory($t);
			$t->save();
			$ts[$obj->id] = $t; // Use the 'given' ID, rather than the new DB ID
		}

		// Second pass, set up neighbours
		foreach ($objs as $obj) {
			$t = $ts[$obj->id];
			foreach ($obj->neighbours as $nid) {
				$n = $ts[$nid]; // again, using the spreadsheet IDs here
				$t->addNeighbour($n);
			}
			$t->save();
		}
		$this->save();
		//return $ts;
	}
}

// vim: ts=3 sw=3 noet :
