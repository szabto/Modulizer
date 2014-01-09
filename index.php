<?php
require_once "Modulizer.php";

Modulizer::register("slim.dns", "dns", array(), function(&$factory, $require, $e) {
	$storage = array();

	$e->add = function($name, $resolveTo) use(&$storage) {
		$storage[$name] = $resolveTo;
	};
	$e->get = function($name) use(&$storage) {
		if( array_key_exists($name, $storage) ) {
			return $storage[$name];
		}
		return null;
	};

	$factory->exports = $e;
}, 1);

    

Modulizer::get("dns", array());

?>  