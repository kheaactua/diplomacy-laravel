<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\Disband as BaseDisband;

/**
 * Skeleton subclass for representing a row from the 'order_disband' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Disband extends BaseDisband
{
	use StaticOrderMethods;

	protected static $cmd = 'DISBAND';
	protected static $format = '%empire% %cmd% %source%';
	protected static $formatRe = '/(DISBAND)\s+([^-]+)-(.*)/';
}
