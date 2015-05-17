<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Order as BaseOrder;
use DiplomacyEngine\Empires\Unit;

/**
 * Skeleton subclass for representing a row from the 'empire_order' table.
 *
 * Saves the orders of each turn.  Required because there is a break in the process between initial orders and required retreats.
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
abstract class Order extends BaseOrder {

	/**
	 * Format of the order, uses replacable strings
	 *
	 * %cmd% %source% %dest%
	 */
	public $format;

	/** The command assoiated with this order */
	public $cmd = "";

	/**
	 * The source territory
	 */
	public $source;

	/**
	 * The destination territory
	 */
	public $dest;

	/**
	 * Unit/Force type (fleet or army)
	 */
	protected $unit;

	/*
	public static function create(
		Unit $unit,
		Empire $empire,
		Territory $source,
		Territory $dest
	) {
		$this->unit= $unit;
		$this->setEmpire($empire);
		$this->source = $source;
		$this->dest   = $dest;
		$this->transcript = array();
	}
	*/

	public function failed() {
		return $this->getStatus() == 'failed';
	}

	public function __toString() {
		return "Abstract order.";
	}

	/** Serialize the order into a string using a format */
	protected function generateOrder($keys, $vals) {
		array_walk($keys, function(&$e) { $e = "/%$e%/"; });
		$str = preg_replace($keys, $vals, $this->format);
		return $str;
	}

	/** Marks the order as failed */
	public function fail($reason='') {
		if (strlen($reason))
			$this->setTranscript($this->getTranscript . "\n" . $reason);
		$this->setStatus('failed');
	}

	/**
	 * Function to return the empire being supported by this order.
	 * This will typically be the empire, except for in support orders.
	 */
	public function supporting() {
		return $this->getEmpire();
	}

	public function validate() {
		if ($this->failed()) return false;

		// Does the empire own the source territory
// TODO make exception for CONVOYS
		if ($this->source->getOccupier() != $this->empire) {
			$this->fail($this->getEmpire() . " does not occupy $this->source");
		}
	}

	/**
	 * Return a list of territories that are involved
	 * in this order.
	 **/
	public function getTerritories() {
		return array($this->source, $this->dest);
	}
}

class Move extends Order {
	public $format = "%empire% %cmd% %unit% %source%-%dest%";

	/** The command assoiated with this order */
	public $cmd = "MOVE";

	public static function create(
		Unit    $unit,
		Empire  $empire,
		State   $source,
		State   $dest
	) {
		$o = new Move;
		$o->setMatch($match);
		$o->setTurn($turn);
		$o->setEmpire($empire);
		$o->setUnit($unit);

		$o->source = $source;
		$o->dest = $dest;
		return $o;
	}

	public function __toString() {
		$str = $this->generateOrder(
			array('empire', 'unit', 'cmd', 'source', 'dest'),
			array($this->empire, $this->unit->__toString(), $this->cmd, $this->source, $this->dest)
		);

		return $str;
	}
}


class Support extends Order {
	public $format = "%empire% %cmd% %aly% %source%-%dest%";

	/** The command assoiated with this order */
	public $cmd = "SUPPORT";

	public static function create(
		Unit    $unit,
		Empire  $empire,
		Empire  $aly,
		State   $source,
		State   $dest
	) {
		$o = new Support;
		$o->setMatch($match);
		$o->setTurn($turn);
		$o->setEmpire($empire);
		$o->setUnit($unit);

		$o->source = $source;
		$o->dest = $dest;
		$o->aly = $aly;
		return $o;
	}
	public function getSupporting() {
		return $this->aly;
	}

	public function __toString() {
		$str = $this->generateOrder(
			array('empire', 'aly', 'unit', 'cmd', 'source', 'dest'),
			array($this->empire, $this->aly, $this->unit->__toString(), $this->cmd, $this->source, $this->dest)
		);

		return $str;
	}

}

// vim: ts=3 sw=3 noet :
