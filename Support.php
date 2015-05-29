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
	// protected function getFormat() { return "%empire% %cmd% "%ally-state%" "%source%" "%dest%"; }
	// protected function getFormatRe() { return '/(SUPPORT)\s+(\w+)\s+([^-]+)-(.*)/'; }
	protected static $cmd = 'SUPPORT';
	protected static $format = '%empire% %cmd% "%ally-state%" "%source%" "%dest%"';
	protected static $formatRe = '/('. self::$cmd .')\s+"([^"]+)"\s+"([^"]+)" "([^"]+)"/';

	/**
	 * Create unsaved (NS=No Save) order
	 */
	public static function createNS(
		Empire  $empire,
		State   $allyState,
		State   $source,
		State   $dest
	) {
		$o = new Support;
		$o->setEmpire($empire);

		$o->setSource($source);
		$o->setDest($dest);
		$o->setAllyState($allyState);
		return $o;
	}
	public function getSupporting() {
		return $this->getAllyState()->getOccupier();
	}

	public function __toString() {
		$str = $this->generateOrder(
			array('empire', 'ally-state', 'cmd', 'source', 'dest'),
			array("[". str_pad($this->getEmpire(),10)."]", $this->getAllyState()->getTerritory(), self::getOrderCommand(), $this->getSource()->getTerritory(), $this->getDest()->getTerritory())
		);
		return $str;
	}

	/**
	 * Given some text, try to build a MOVE order.
	 */
	public static function interpretText($command, Match $match, Empire $empire) {
		if (preg_match(self::getFormatRe(), $command, $matches)) {
			// 1 = cmd
			// 2 = source
			// 3 = ally-state (territory)
			// 4 = ally's destination
// print_r($matches);


			// Match current territory
			try {
				$source = $match->getGame()->lookupTerritory($matches[2], $match, $empire);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
			}

			// Match the Ally State
			try {
				$allyState = $match->getGame()->lookupTerritory($matches[3], $match);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
			}
// $config->system->db->useDebug(false);


			try {
				// Cannot specify ally here to help the match, as the ally doesn't
				// currently occupy the destination territory
				$dest = $match->getGame()->lookupTerritory($matches[4], $match);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
			}

			return self::createNS($empire, $allyState, $source, $dest);
		}
		throw new InvalidOrderException("Could not match order text $command");
	}
	public function getActiveStates() {
		return array($this->getSource(), $this->getDest());
	}

	/**
	 * Export to JSON
	 */
	public function __toArray() {
		$ret = parent::__toArray();
		$ret['ally'] = $this->getAlly()->__toArray();
		$ret['dest'] = $this->getDest()->getTerritory()->__toArray();
		return $ret;
	}
}

// vim: ts=3 sw=3 noet :
