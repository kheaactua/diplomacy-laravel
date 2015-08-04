<?php

namespace App\Models;

use App\Models\Base\Convoy as BaseConvoy;

/**
 * Skeleton subclass for representing a row from the 'order_convoy' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Convoy extends BaseConvoy
{
	use StaticOrderMethods;

	protected static $cmd = 'CONVOY';
	protected static $format = '%empire% %cmd% "%source%" "%troupe%" "%dest%"';
	protected static $formatRe = '/(CONVOY)\s+"([^"]+)"\s+"([^"]+)\s+"([^"]+)"/';

	/**
	 * Create unsaved (NS=No Save) order
	 */
	public static function createNS(
		Empire  $empire,
		State   $source,
		State   $troupe,
		State   $dest
	) {
		$o = new Support;
		$o->setEmpire($empire);

		$o->setTroupe($troupe);
		$o->setSource($source);
		$o->setDest($dest);
		return $o;
	}

	public function getActiveStates() {
		return array($this->getSource(), $this->getMiddle(), $this->getDest());
	}

	/**
	 * Export to JSON
	 */
	public function __toArray() {
		$ret = parent::__toArray();
		$ret['troupe'] = $this->getTroupe()->getTerritory()->__toArray();
		$ret['dest']   = $this->getDest()->getTerritory()->__toArray();
		return $ret;
	}

	public function __toString() {
		$str = $this->generateOrder(
			array('empire', 'source', 'cmd', 'troupe', 'dest'),
			array("[". str_pad($this->getEmpire(),10)."]", $this->getSource()->getTerritory(), self::getOrderCommand(), $this->getTroupe()->getTerritory(), $this->getDest()->getTerritory())
		);
		return $str;
	}

	/**
	 * Given some text, try to build a MOVE order.
	 */
	public static function interpretText($command, Match $match, Empire $empire) {
		if (preg_match(self::getFormatRe(), $command, $matches)) {
			// 1 = cmd
			// 2 = Source
			// 3 = Troupe Territory
			// 4 = Destination
// print_r($matches);

			// Match the Ally State
			try {
				$troupeState = $match->getGame()->lookupTerritory($matches[2], $match);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
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

			return self::createNS($empire, $source, $troupState, $dest);
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
