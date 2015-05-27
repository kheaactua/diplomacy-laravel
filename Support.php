<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Support as BaseSupport;
use DiplomacyEngine\MultiTerritory;

/**
 * Skeleton subclass for representing a row from the 'order_support' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Support extends BaseSupport implements MultiTerritory {
	use StaticOrderMethods;

	// Static member variables with inheritance is irratating,
	// so hardcoding these into getters instead of member variables
	// protected function getFormat() { return "%empire% %cmd% %ally% %source%-%dest%"; }
	// protected function getFormatRe() { return '/(SUPPORT)\s+(\w+)\s+([^-]+)-(.*)/'; }
	protected static $cmd = 'SUPPORT';
	protected static $format = '%empire% %cmd% %ally% %source%-%dest%';
	protected static $formatRe = '/(SUPPORT)\s+(\w+)\s+([^-]+)-(.*)/';

	/**
	 * Create unsaved (NS=No Save) order
	 */
	public static function createNS(
		Empire  $empire,
		//Unit    $support_unit,
		Empire  $ally,
		//Empire  $ally_unit,
		State   $source,
		State   $dest
	) {
		$o = new Support;
		$o->setEmpire($empire);

		$o->setSource($source);
		$o->setDest($dest);
		$o->setAlly($ally);
		return $o;
	}
	public function getSupporting() {
		return $this->getAlly();
	}

	public function __toString() {
		$str = $this->generateOrder(
			array('empire', 'ally', 'cmd', 'source', 'dest'),
			array("[". str_pad($this->getEmpire(),10)."]", $this->getAlly(), self::getOrderCommand(), $this->getSource()->getTerritory(), $this->getDest()->getTerritory())
		);
		return $str;
	}

	/**
	 * Given some text, try to build a MOVE order.
	 */
	public static function interpretText($command, Match $match, Empire $empire) {
		if (preg_match(self::getFormatRe(), $command, $matches)) {
			// 1 = cmd
			// 2 = ally (empire)
			// 3 = source
			// 4 = ally's destination
// print_r($matches);

			// // Match the unit
			// try {
			// 	$unit = new Unit($matches[2]);
			// } catch (DiplomacyOrm\InvalidUnitException $e) {
			// 	return new InvalidOrderException("Could not match unit type {$matches[2]}");
			// }

			// Match ally
// global $config; $config->system->db->useDebug(true);
			$ally = EmpireQuery::create()->filterByGame($match->getGame())
				->filterByAbbr($matches[2])
				->_or()
				->filterByName($matches[2])
				->_or()
				->filterByNameShort($matches[2])
				->findOne()
			;
// $config->system->db->useDebug(false);
// print "ally = ". get_class($ally) . "\n";
			if (!($ally instanceof Empire)) {
				throw new InvalidOrderException("Cannot match ally {$matches[2]}");
			}

			// Match the territories
			try {
				$source = $match->getGame()->lookupTerritory($matches[3], $match, $empire);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
			}

			try {
				// Cannot specify ally here to help the match, as the ally doesn't
				// currently occupy the destination territory
				$dest = $match->getGame()->lookupTerritory($matches[4], $match);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
			}

			return self::createNS($empire, $ally, $source, $dest);
		}
		throw new InvalidOrderException("Could not match order text $command");
	}
	public function getActiveStates() {
		return array($this->getSource(), $this->getDest());
	}
}

// vim: ts=3 sw=3 noet :
