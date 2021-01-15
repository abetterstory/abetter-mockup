<?php

// ---

if (!function_exists('_lipsum')) {

	function _lipsum() {
		return \ABetter\Mockup\Lipsum::get(...func_get_args());
	}

}

if (!function_exists('_pixsum')) {

	function _pixsum() {
		return \ABetter\Mockup\Pixsum::get(...func_get_args());
	}

}
