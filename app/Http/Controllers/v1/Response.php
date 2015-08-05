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
	const INVALID_GAME=-4;
	const INVALID_EMPIRE=-5;
	const INVALID_ORDER=-6;

	const TURN_ERROR=-50;
	const TURN_RETREATS_REQUIRED=-20;
	const TURN_SUPPLY_SEASON=21;

	public $code;
	public $msg;
	public $data;

	public function __construct($code = self::SUCCESS, $msg = '', $data = null) {
		$this->code = $code;
		$this->msg  = $msg;
		$this->data = $data;
	}

	public function __toString() {
		$str = "Response:\n";
		$str .= "\t- code: $this->code\n";
		$str .= "\t- msg: $this->msg\n";
		return $str;
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
