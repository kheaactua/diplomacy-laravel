<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Hold as BaseHold;

/**
 * Skeleton subclass for representing a row from the 'order_hold' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Hold extends BaseHold
{
	use StaticOrderMethods;

	protected static $cmd = 'HOLD';
	protected static $format = '%empire% %cmd% %source%';
	protected static $formatRe = '/('. self::$cmd .')\s+"([^"]+)"/';

	/**
	 * Create unsaved (NS=No Save) order
	 */
	public static function createNS(
		Empire  $empire,
		State   $source
	) {
		$o = new Hold;
		$o->setEmpire($empire);

		$o->setSource($source);
		return $o;
	}

	/**
	 * Given some text, try to build a HOLD order.
	 */
	public static function interpretText($command, Match $match, Empire $empire) {
		if (preg_match(self::getFormatRe(), $command, $matches)) {
			// 1 = cmd
			// 2 = source

			// Match the territories
			try {
				$source = $match->getGame()->lookupTerritory($matches[2], $match, $empire);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
			}

			return self::createNS($empire, $source);
		}
		throw new InvalidOrderException("Could not match order text $command");
	}

	/**
	 * Export to JSON
	 */
	public function __toArray() {
		return parent::__toArray();
	}
}

// vim: ts=3 sw=3 noet :
