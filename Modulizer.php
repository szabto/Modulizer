<?php
//require_once "Bag.php";

class ModulizerException Extends Exception {};

class __proto__ extends stdClass {
	public function __call($fn, $args) {
		return call_user_func_array($this->{$fn}, $args);
	}
}

class Jar {
    static $aliases = array();
    static $modules = array();

    static function exists($module_name) {
        if(array_key_exists($module_name, self::$modules))
            return true;
        if(array_key_exists($module_name, self::$aliases))
            return true;
        return false;
    }

    static function &get($module_name) {
        $module_name = strtolower($module_name);

        if(self::exists($module_name)) {
            if(array_key_exists($module_name, self::$modules))
                return self::$modules[$module_name];

            return self::$modules[self::$aliases[$module_name]];
        }

        throw new ModulizerException("Module with name {$module_name} is not in the Jar..");
    }

    static function add($module_name, $exports) {
        self::$modules[strtolower($module_name)] = $exports;
    }

    static function alias($alias, $module) {
        static::$aliases[strtolower($alias)] = strtolower($module);
    }
}

class Factory extends __proto__ {
    public $modulizer;
    public $exports = null;

    public function __construct() {
        $this->modulizer = new __proto__();
        $this->modulizer->loadModule = array();
    }
}

class Modulizer {

    static $factory;

    public static function init($configuration = array()) {
        self::$factory = new Factory();
    }

    public static function register() {
        $args = func_get_args();
        $args_count = func_num_args();

        //module skeleton
        $module = array(
            "name" => null,
            "alias" => null,
            "callable" => null,
            "dependencies" => array(),
        );

        if($args_count < 2)
            throw new ModulizerException("At least 2 parameters needed...");

        //get name of the module
        if(is_array($args[0])) {
            $module['name'] = $args[0][0];
            $module['alias'] = $args[0][1];
        } else {
            $module['name'] = $args[0];
        }

        //get function and dependencies
        if(is_callable($args[1])) {
            $module['callable'] = $args[1];
        }
        if(($f = is_array($args[1])) || (($args_count == 3) && (is_array($args[2])))) {
            $module['dependencies'] = ($f? $args[1] : $args[2]);
        }

        //lets start loading the module
        //first we check if all dependencies are loaded and if not load them :D
        foreach($module['dependencies'] as $dependency) {
            if(!Jar::exists($dependency))
                self::load($dependency);
        }
        $f = &self::$factory;
        //now run this shit
        call_user_func($module['callable'], $f, new __proto__);

        Jar::add($module['name'], self::$factory->exports);
        self::$factory->exports = null;

        if($module['alias'])
            Jar::alias($module['alias'], $module['name']);
    }

    public static function &get($module_name) {
        if(Jar::exists($module_name)) {
            //if its loaded...
            return Jar::get($module_name);
        } else {
            //if not try to call factory function for it
            self::load($module_name);

            if(!Jar::exists($module_name))
                throw new ModulizerException("Requested module ({$module_name}) wasn't loaded, tried call 'loadModule' callbacks still no luck!");

            return Jar::get($module_name);
        }
    }

    public static function load($module_name) {
        $f = &self::$factory;
        foreach(self::$factory->modulizer->loadModule as $loader)
            call_user_func($loader, $f, $module_name);
    }
}