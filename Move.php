<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Move as BaseMove;
use DiplomacyEngine\MultiTerritory;

/**
 * Skeleton subclass for representing a row from the 'order_move' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Move extends BaseMove implements MultiTerritory {
	use StaticOrderMethods;

	// Static member variables with inheritance is irratating,
	// so hardcoding these into getters instead of member variables
	protected static $cmd = 'MOVE';
	//protected function getFormat() { return '%empire% %cmd% %unit% %source%-%dest%'; }
	//protected function getFormatRe() { return '/(MOVE)\s+(army|a|fleet|f)\s+([^-]+)-(.*)/'; }
	protected static $format = '%empire% %cmd% %unit% %source%-%dest%';
	protected static $formatRe = '/(MOVE)\s+(army|a|fleet|f)\s+([^-]+)-(.*)/';

	/**
	 * Create unsaved (NS=No Save) order
	 */
	public static function createNS(
		Empire  $empire,
		Unit    $unit,
		State   $source,
		State   $dest
	) {
		$o = new Move;
		$o->setEmpire($empire);
		$o->setUnit($unit->enum());

		$o->setSource($source);
		$o->setDest($dest);
		return $o;
	}

	public function __toString() {
		$str = $this->generateOrder(
			array('empire', 'unit', 'cmd', 'source', 'dest'),
			array("[". str_pad($this->getEmpire(),10)."]", new Unit($this->unit), self::$cmd, $this->getSource()->getTerritory(), $this->getDest()->getTerritory())
		);

		return $str;
	}

	/**
	 * Given some text, try to build a MOVE order.
	 */
	public static function interpretText($command, Match $match, Empire $empire) {
		if (preg_match(self::getFormatRe(), $command, $matches)) {
			// 1 = cmd
			// 2 = unit
			// 3 = source
			// 4 = dest

			// Match the unit
			try {
				$unit = new Unit($matches[2]);
			} catch (DiplomacyOrm\InvalidUnitException $e) {
				throw new InvalidOrderException("Could not match unit type {$matches[2]}");
			}

			// Match the territories
			try {
				$source = $match->getGame()->lookupTerritory($matches[3], $match, $empire);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
			}

			try {
				$dest = $match->getGame()->lookupTerritory($matches[4], $match);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
			}

			return self::createNS($empire, $unit, $source, $dest);
		}
	}

	public function getActiveStates() {
		return array($this->getSource(), $this->getDest());
	}
}

// vim: ts=3 sw=3 noet :
