<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Supply as BaseSupply;

/**
 * Skeleton subclass for representing a row from the 'order_supply' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Supply extends BaseSupply {
	use StaticOrderMethods;

	// Static member variables with inheritance is irratating,
	// so hardcoding these into getters instead of member variables
	protected static $cmd = 'SUPPLY';
	protected static $format = '%empire% %cmd% "%dest%"';
	protected static $formatRe = '/('. self::$cmd . ')\s+"([^"]+)"/';

	/**
	 * Supply unsaved (NS=No Save) order
	 */
	public static function createNS(
		Empire  $empire,
		State   $dest
	) {
		$o = new Move;
		$o->setSource($dest);
		return $o;
	}

	/**
	 * Validate the order.
	 * @return bool Whether the order is good
	 */
	public function validate($full = true) {
		$res = parent::Validate($full);
		if (!$res) return $res;

		if (!$this->getSource()->getTerritory()->getIsSupply()) {
			$this->fail($this->getSource()->getTerritory() . ' has no supply depots');
			return false;
		}
		if (is_null($this->getSource()->getTerritory()->getUnitId())) {
			$this->fail($this->getSource()->getTerritory() . ' is not empty');
			return false;
		}
	}

	public function __toString() {
		$str = $this->generateOrder(
			array('empire', 'cmd', 'dest'),
			array("[". str_pad($this->getEmpire(),10)."]", self::$cmd, $this->getSource()->getTerritory())
		);

		return $str;
	}

	/**
	 * Given some text, try to build a MOVE order.
	 */
	public static function interpretText($command, Match $match, Empire $empire) {
		if (preg_match(self::getFormatRe(), $command, $matches)) {
			// 1 = cmd
			// 2 = dest

			// Match the territories
			try {
				$dest = $match->getGame()->lookupTerritory($matches[2], $match, $empire);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
			}

			return self::createNS($empire, $dest);
		}
		throw new InvalidOrderException("Could not match order text $command");
	}

}

// vim: ts=3 sw=3 noet :
