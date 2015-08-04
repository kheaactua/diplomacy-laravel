<?php

namespace App\Models;

use App\Models\Base\Move as BaseMove;
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
	//protected function getFormat() { return '%empire% %cmd% %source%-%dest%'; }
	//protected function getFormatRe() { return '/(MOVE)\s+([^-]+)-(.*)/'; }
	protected static $format = '%empire% %cmd% "%source%" "%dest%"';
	protected static $formatRe = '/(MOVE)\s+"([^"]+)"\s+"(.*)"/';

	/**
	 * Create unsaved (NS=No Save) order
	 */
	public static function createNS(
		Empire  $empire,
		State   $source,
		State   $dest
	) {
		$o = new Move;
		$o->setEmpire($empire);

		$o->setSource($source);
		$o->setDest($dest);
		return $o;
	}

	/**
	 * Validate the order.
	 * @return bool Whether the order is good
	 */
	public function validate($full = true) {
		$res = parent::Validate($full);
		if (!$res) return $res;

		if ($this->getSource()->getUnitType() == 'fleet' && $this->getDest()->getTerritory()->getType() == 'land') {
			$this->fail('Cannot move fleet out of water');
			return false;
		}
		if ($this->getSource()->getUnitType() == 'army' && $this->getDest()->getTerritory()->getType() == 'water') {
			$this->fail('Cannot move army into water');
			return false;
		}
	}

	public function __toString() {
		$str = $this->generateOrder(
			array('empire', 'cmd', 'source', 'dest'),
			array("[". str_pad($this->getEmpire(),10)."]", self::$cmd, $this->getSource()->getTerritory(), $this->getDest()->getTerritory())
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
			// 3 = dest

			// Match the territories
			try {
				$source = $match->getGame()->lookupTerritory($matches[2], $match, $empire);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
			}

			try {
				$dest = $match->getGame()->lookupTerritory($matches[3], $match);
			} catch (TerritoryMatchException $ex) {
				throw new InvalidOrderException($ex->getMessage());
			}

			return self::createNS($empire, $source, $dest);
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
		$ret['dest'] = $this->getDest()->getTerritory()->__toArray();
		return $ret;
	}
}

// vim: ts=3 sw=3 noet :
