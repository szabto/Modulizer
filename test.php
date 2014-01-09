<?php

$bag = array();
$bag["aliases"] = array();
$bag["dependenciesLoaded"] = array();
$bag["instances"] = array();

/**
 * Class proto
 */
class proto {
	public function __call($fn, $args) {
		return call_user_func_array($this->{$fn}, $args);
	}
}

/**
 * Class LoaderException
 */
class LoaderException Extends Exception {};

/**
 * @param      $name
 * @param      $alias
 * @param      $dependencies
 * @param null $fnc
 * @param int  $comfort
 *
 * @throws LoaderException
 */
function register($name, $alias, $dependencies, $fnc = null, $comfort = 0) {
	global $bag;

	if( is_array($alias) ) {
		$comfort = $fnc;
		$fnc = $dependencies;
		$dependencies = $alias;
	}

	if( in_array($name, $bag["dependenciesLoaded"]) || (!is_array($alias) && in_array($alias, $bag["dependenciesLoaded"])) ) {
		if( (array_key_exists($name, $bag) && $bag[$name]["loaded"]) || (!is_Array($alias) && array_key_exists($alias, $bag) && $bag[$alias]["loaded"]) )
			throw new LoaderException("Registered an existing module ({$name})");
	}

	for($i=0;$i<count($dependencies);$i++) {
		if( !in_array($dependencies[$i], $bag["dependenciesLoaded"]) ) {
			throw new LoaderException("Call a module with unresolved dependencies");
		}
	}

	$factory = new stdclass;
	if( $fnc && !$comfort ) {
		$fnc($factory, function($n) { return req($n);}, new proto);
	}

	$bag["dependenciesLoaded"] = array_merge($bag["dependenciesLoaded"], array($name));
	if( !is_array($alias) && $alias ) {
		$bag["aliases"][$alias] = $name;
		$bag["dependenciesLoaded"] = array_merge($bag["dependenciesLoaded"], array($alias));
	}

	if( $comfort ) {
		$e = new proto;
		$bag[$name] = array(
			"exports" => $e ,
			"loaded" => false,
			"a" => $name,
			"b" => $dependencies,
			"c" => $fnc
		);
		return;
	}

	if( isset($factory->exports) )
		$exports = $factory->exports;
	else
		$exports = null;

	if( isset($factory->global) ) {
		$GLOBALS = array_merge($GLOBALS, $factory->global);
	}

	$bag[$name] = array(
		"exports" => $exports,
		"dependencies" => $dependencies,
		"loaded" => true
	);
}

/**
 * @param      $name
 * @param bool $instance
 *
 * @return mixed
 */
function req($name, $instance = false) {
	global $bag;

	if( $instance && array_key_exists($name, $bag["instances"]) )
		return $bag["instances"][$name];

	$exp = null;
	if(array_key_exists($name, $bag)) {
		$exp = $bag[$name];
	}
	else {
		if(array_key_exists($name, $bag["aliases"])) {
			$exp = $bag[$bag["aliases"][$name]];
		}
	}

	if( $exp ) {
		if( isset($exp["loaded"]) && !$exp["loaded"] ) {
			register($exp["a"], $exp["b"], $exp["c"]);
			$exp["exports"] = req($name, $instance);
		}
	}
	if( $instance ){
		$bag["instances"][$name] = $exp["exports"];
		return $bag["instances"][$name];
	}
	else {
		$e = $exp["exports"];
		return $e;
	}
}

/**
 *
 */
register("slim.dns", "dns", array(), function(&$factory, $require, $e) {
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

register("slim.dnsdep", array("dns"), function(&$a, $b) {
	$dns = $b("dns", true);

	$dns->add("loool", "bla");

	echo $dns->get("loool");
});

$dd = req("dns");
echo $dd->get("loool");

?>