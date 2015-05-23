<?php

namespace DiplomacyEngineRestApi\v1;

/**
 * Little container class to ensure consistant responses.
 */

class Response {
	const SUCCESS=0;
	const UNKNOWN_EXCEPTION=-2;
	const INVALID_ROUTE=-100;
	const INVALID_MATCH=-3;
	const INVALID_EMPIRE=-5;
	const INVALID_GAME=-4;

	public $code;
	public $msg;
	public $data;

	public __construct($code = self::SUCCESS, $msg = '', $data = null) {
		$this->code = $code;
		$this->msg  = $msg;
		$this->data = $data;
	}

	public function __toArray() {
		return array(
			'code' => $this->code,
			'msg'  => $this->msg,
			'data' => $this->data,
		);
	}
	public function fail($code, $msg) {
		$this->code = $code;
		$this->msg  = $msg;
	}
}

// vim: sw=3 sts=3 ts=3 noet :
