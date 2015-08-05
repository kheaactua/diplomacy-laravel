<?php

namespace DiplomacyEngineRestApi\v1;

class RouteHandler {
	protected $mlog;
	protected $auth;
	protected $systemConfig;

	public function __construct() {
		global $MLOG;
		$this->mlog = $MLOG;

		// Get system config
		$this->systemConfig = \Configurator\Constants::getRoot();
	}

	public function defaultRoute() {
		$resp = new Response(Response::INVALID_ROUTE, 'Cannot find route on '. get_class($this));
		return $resp->__toArray();
	}
}

// Thought this was declared in the PHP Rest Service, but turns out it was
// but a message.
class MissingRequiredArgumentException extends \Exception { };

// vim: sw=3 sts=3 ts=3 noet :
