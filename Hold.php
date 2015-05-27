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
	protected static $formatRe = '/(HOLD)\s+([^-]+)-(.*)/';


}

// vim: ts=3 sw=3 noet :
