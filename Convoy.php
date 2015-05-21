<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Convoy as BaseConvoy;

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
	protected static $format = '%empire% %cmd% %source%-%dest%';
	protected static $formatRe = '/(CONVOY)\s+([^-]+)-(.*)/';


	public function getActiveStates() {
		return array($this->getSource(), $this->getMiddle(), $this->getDest());
	}
}

// vim: ts=3 sw=3 noet :
