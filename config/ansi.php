<?php

/**
 * Define some ansi colours to help with message parsing.
 */

$s="\033[1;";

return [
	'red'    => "{$s}31m",
	'blue'   => "{$s}34m",
	'green'  => "{$s}32m",
	'cyan'   => "{$s}36m",
	'yellow' => "{$s}33m",
	'clear'  => "{$s}0m",
];

// vim: ts=3 sw=3 sts=3 noet ffs=unix :
