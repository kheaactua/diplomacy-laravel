<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Game as BaseGame;
use DiplomacyEngine\iEmpire;
use DiplomacyEngine\Unit;
use Propel\Runtime\ActiveQuery\Criteria;

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

	/**
	 * Try to look up a game territory given a string.  This is intended
	 * to be as loose as possible to match what people type.  Only return
	 * if one result is found.
	 *
	 * @param $str Territory name, or abbreviation, or anything we put in
	 *             the system that a user may use
	 * @param Match Requires a match such that it can use the current turn, 
	 *              without this several results (one per turn) will be returned 
	 *              everytime
	 * @param Empire Sometimes we can limit what territories might be
	 *               being searched for by the empire.  In this case,
	 *               use the game state to filter the results.
	 * @return State
	 * @throws TerritoryMatchException
	 */
	public function lookupTerritory($str, Match $match, Empire $empire = null) {
global $config; $config->system->db->useDebug(true);
		$query = StateQuery::create()
			->filterByTurn($match->getCurrentTurn())
			->_if($empire instanceof Empire)
				->filterByOccupier($empire)
			->_endif() 
			->join('State.Territory')
			->useTerritoryQuery()
				->filterByGame($this) // probably unnecessary
				->filterByName($str.'%', Criteria::LIKE)
			->endUse()
		;

		$ts = $query->find();
//$config->system->db->useDebug(false);
		if (count($ts) == 1) {
			return $ts[0];
		} elseif (count($ts) > 1) {
			$match_names = [];
			foreach ($ts as $t)
				$match_names[] = $t->getTerritory()->getName();
			throw new MultiTerritoryMatchException("Multiple matches for $str: '". join("', '", $match_names) . "'");
		} else {
			throw new NoTerritoryMatchException("No match for $str");
		}
	}
}

class GameException extends \Exception { };
class TerritoryMatchException extends GameException { };
class MultiTerritoryMatchException extends TerritoryMatchException { };
class NoTerritoryMatchException extends TerritoryMatchException { };


// vim: ts=3 sw=3 noet :
