<?php
require_once "Modulizer.php";

Modulizer::init();

Modulizer::register("test", function(&$f, $e) {

    $f->modulizer->loadModule[] = function(&$f, $module_name) {
        Modulizer::register($module_name, function(&$f, $e) use(&$module_name) {
            echo "Hi! I'm a module and I was created by the test module, which had a hook on 'loadModule'... my name is {$module_name} btw!\n";
        });
    };

    $f->exports = $e;
});

Modulizer::register("foo", function(&$f, $e) {
    $e->message = 'hello';

    $f->exports = $e;
}, array("test"));

Modulizer::register("bar", function(&$f, $e) {
    echo "Hi! I'm 'bar' and foo has a message for yaa: ".Modulizer::get('foo')->message."\n";
}, array("foo", "foobar"));
/*
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

    

Modulizer::get("dns", array());*/

?>  