<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Order as BaseOrder;
use DiplomacyOrm\Move;
use DiplomacyOrm\Support;
use DiplomacyOrm\Convoy;
use DiplomacyOrm\Disband;
use DiplomacyOrm\Hold;
use DiplomacyEngine\Unit;

/**
 * Trait to get around inheritance conflicting with static calls
 */
trait StaticOrderMethods {
	public static function getOrderCommand() { return self::$cmd;      }
	public static function getFormat()       { return self::$format;   }
	public static function getFormatRe()     { return self::$formatRe; }
}

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
//abstract class Order extends BaseOrder {
class Order extends BaseOrder {
	use StaticOrderMethods;

	// Static member variables with inheritance is irratating,
	// so hardcoding these into getters instead of member variables
	protected static $cmd = 'N/A';
	//protected function getFormat() { return '%empire% %cmd% %unit% %source%-%dest%'; }
	//protected function getFormatRe() { return '/(MOVE)\s+(army|a|fleet|f)\s+([^-]+)-(.*)/'; }
	protected static $format = 'no format';
	protected static $formatRe = '//';

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

// 	/**
// 	 * Populate our member variables based on the command text.
// 	 * Inefficient, might be changed later.  This is a product of
// 	 * all orders having different syntax
// 	 **/
// 	public function init() {
// 		if ($this->getOrderId()) {
// 			// If we're an object already,
// 			// Populate source and dest.
// print "initializing order\n";
// 			$c = $this->getHandler();
// 			call_user_func('DiplomacyOrm\\'.$c.'::interpretText', $this->getCommand(), $this->getTurn()->getMatch(), $this->getEmpire);
// 		} else {
// print "Not initializing order\n";
// 		}
// 	}

	public function failed() {
		return $this->getStatus() == 'failed';
	}

	public function __toString() {
		return "Abstract order.";
	}

	/** Serialize the order into a string using a format */
	protected function generateOrder($keys, $vals) {
		array_walk($keys, function(&$e) { $e = "/%$e%/"; });
		$str = preg_replace($keys, $vals, call_user_func(array(get_class($this), 'getFormat')));
		return $str;
	}

	/** Marks the order as failed */
	public function fail($reason='') {
		$this->addToTranscript($reason);
		$this->setStatus('failed');
	}

	public function addToTranscript($str = '') {
		if (strlen($str))
			$this->setTranscript($this->getTranscript() . "\n" . $str);
	}

	/**
	 * Function to return the empire being supported by this order.
	 * This will typically be the empire, except for in support orders.
	 *
	 * @return Empire
	 */
	public function getSupporting() {
		return $this->getEmpire();
	}

	public function validate() {
		if ($this->failed()) return false;

		// Does the empire own the source territory
// TODO make exception for CONVOYS
		if ($this->getSource()->getOccupier() != $this->getEmpire()) {
			$this->fail($this->getEmpire() . " does not occupy ". $this->getSource()->getTerritory());
		}
	}

	/**
	 * Return a list of territories that are involved
	 * in this order.
	 *
	 * @return array(State, ...)
	 **/
	public function getActiveStates() {
		return array($this->getSource());
	}

	/**
	 * Given text, attempts to interpret the text as an order
	 *
	 * @return Order
	 */
	public static function interpretText($command, Match $match, Empire $empire) {
		// Try to delegate this to a subfunction asap
		$command = trim($command);

		// Collect all the order types
		// Slow, might want to hard code this...
		$subclasses = array('Move', 'Support', 'Hold', 'Disband', 'Convoy', 'Retreat');

		// First word should always be the order
		if (preg_match('/^(\w+)\s/', $command, $matches)) {
			$cmd = $matches[1];

			foreach ($subclasses as $sc) {
				$sc = __NAMESPACE__."\\$sc";
// print "[$sc] orderCmd=". call_user_func(array($sc, 'getOrderCommand')) . " == $cmd\n";
				if (strcasecmp(call_user_func(array($sc, 'getOrderCommand')), $cmd) === 0) {
					// Found our delegate!
					return $sc::interpretText($command, $match, $empire);
				}
			}
			trigger_error("Could not find order delegate for '$cmd'");
		} else {
			trigger_error("No order given in command: '$command'");
		}
	}

	/**
	 * Not proper downcasting, really replacing an object with it's
	 * child.
	 */
	public static function downCast(Order $o) {
		if ($o->hasChildObject()) {
			//print "Downcasting to ". $o->getDescendantClass() . "\n";
			return $o->getChildObject();
		} else {
			return $o;
		}
	}
}

class OrderException extends \Exception { };
class InvalidOrderException extends OrderException { };

// vim: ts=3 sw=3 noet :
