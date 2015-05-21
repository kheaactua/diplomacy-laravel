<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Retreat as BaseRetreat;

/**
 * Skeleton subclass for representing a row from the 'order_retreat' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Retreat extends BaseRetreat
{
	use StaticOrderMethods;

	protected static $cmd = 'RETREAT';
	protected static $format = '%empire% %cmd% %source%-%dest%';
	protected static $formatRe = '/(RETREAT)\s+([^-]+)-(.*)/';

	/**
	 * Create unsaved (NS=No Save) order
	 */
	public static function createNS(
		Empire  $empire,
		//Unit    $support_unit,
		State   $source,
		State   $dest
	) {
		$o = new Retreat;
		$o->setEmpire($empire);

		$o->setSource($source);
		$o->setDest($dest);
		return $o;
	}

	public function __toString() {
		$str = $this->generateOrder(
			array('empire', 'cmd', 'source', 'dest'),
			array("[". str_pad($this->getEmpire(),10)."]", self::$cmd, $this->getSource()->getTerritory(), $this->getDest()->getTerritory())
		);

		return $str;
	}

	public function getActiveStates() {
		return array($this->getSource(), $this->getDest());
	}

	/**
	 * Given some text, try to build a RETREAT order.
	 */
	public static function interpretText($command, Match $match, Empire $empire) {
		if (preg_match(self::getFormatRe(), $command, $matches)) {
			// 1 = cmd
			// 2 = source
			// 3 = dest

			// Match the territories
			try {
				$source = $match->getGame()->lookupTerritory($matches[2], $match, $empire);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
			}

			try {
				$dest = $match->getGame()->lookupTerritory($matches[3]);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
			}

			return self::createNS($empire, $source, $dest);
		}
	}


}

// vim: ts=3 sw=3 noet :
