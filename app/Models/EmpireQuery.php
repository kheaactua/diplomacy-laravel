<?php

namespace DiplomacyOrm;

use DiplomacyOrm\Base\EmpireQuery as BaseEmpireQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Skeleton subclass for performing query and update operations on the 'empire' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class EmpireQuery extends BaseEmpireQuery {
	/** Often I want to be able to match by ID or some text, this function
	 * is a very general match.  Tried to mimic pattern of filter functions, but it didn't work out, I'm out of time, etc.. */
	public static function getByNameOrId($key) {
		$empire = parent::create()
				->filterByPrimaryKey($key)
			->_or()
				->filterByName("%$key%", Criteria::LIKE)
			->_or()
				->filterByAbbr("%$key%")
			->findOne();

		return $empire;
	}
}

// vim: ts=4 sts=0 sw=4 noexpandtab :
