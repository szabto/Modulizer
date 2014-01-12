<?php
//require_once "Bag.php";

class Modulizer_Exception Extends Exception {};

class __proto__ extends stdClass {
	public function __call($fn, $args) {
		return call_user_func_array($this->{$fn}, $args);
	}
}

class Modulizer_Jar {
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

        throw new Modulizer_Exception("Module with name {$module_name} is not in the Jar..");
    }

    static function add($module_name, $exports) {
        self::$modules[strtolower($module_name)] = $exports;
    }

    static function alias($alias, $module) {
        static::$aliases[strtolower($alias)] = strtolower($module);
    }
}

class Modulizer_ClassLoader {
    protected $namespaces;
    protected $aliases;

    public function __construct() {
        $this->namespaces = array();
        $this->aliases = array();
        spl_autoload_register(array(&$this, "load"));
    }

    public function addNamespace($ns, $path) {
        if(is_array($this->namespaces[$ns])) {
            $this->namespaces[$ns][$path];
            return;
        }
        $this->namespaces[$ns] = array($path);
    }

    public function addAlias($namespace, $alias) {
        $this->aliases[$namespace] = $alias;
    }

    public function load($class_name, $test_for_alias = true) {
        //reqular classes without alias
        foreach($this->namespaces as $namespace => $path) {
            if(strpos($class_name, $namespace) !== FALSE) {
                $class_path = str_replace(array($namespace, "\\"), array("", "/"), $class_name);
                if(file_exists($class_file = $path.$class_path.".php")) {
                    require_once $class_file;
                    return;
                }
            }
        }

        if(!$test_for_alias) return;
        //maybe its a alias for a class
        foreach($this->aliases as $ns => $alias) {
            if(strrpos($class_name, $alias) !== FALSE) {
                $__class_name = str_replace($alias, $ns, $class_name);
                $this->load($__class_name, false);
                class_alias($__class_name, $class_name);
                return;
            }
        }
    }
}

class Factory extends __proto__ {
    public $classLoader;
    public $moduleLoaders = array();
    public $configuration = array();
    public $exports = null;

    public function __construct() {
        /* Download module from repository */
        $this->moduleLoaders[] = function(&$factory, $module_name) {};
        /* end of module downloader */
        /* Default module loader mechanism */
        $this->moduleLoaders[] = function(&$factory, $module_name) {

            $modules_dirs = array($factory->getConfig('private_modules_dir'), $factory->getConfig('shared_modules_dir'));

            foreach($modules_dirs as $dir) {
                if(file_exists($module_file = $dir.$module_name."/module.php")) {
                    require_once $module_file;
                }
            }
        };
        /* end of module loader */
    }

    public function addModuleLoader($callable) {
        $this->moduleLoaders[] = $callable;
    }

    public function setConfig($c) {
        $this->configuration = array_merge(array("shared_modules_dir" => __DIR__.'/shared_modules/', "private_modules_dir" => __DIR__.'/private_modules/'), $c);
    }

    public function getConfig($key) {
        return $this->configuration[$key];
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
            throw new Modulizer_Exception("At least 2 parameters needed...");

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
            if(!Modulizer_Jar::exists($dependency))
                self::load($dependency);
        }
        $f = &self::$factory;
        //now run this shit
        call_user_func($module['callable'], $f, new __proto__);

        Modulizer_Jar::add($module['name'], self::$factory->exports);
        self::$factory->exports = null;

        $ns = str_replace(".", "\\", $module['name']);
        self::$factory->classLoader->addNamespace($ns, self::$factory->getConfig('private_modules_dir').$module['name']."/src/");
        self::$factory->classLoader->addNamespace($ns, self::$factory->getConfig('shared_modules_dir').$module['name']."/src/");

        if($module['alias']) {
            Modulizer_Jar::alias($module['alias'], $module['name']);
            self::$factory->classLoader->addAlias($ns, $module['alias']);
        }

    }

    public static function &get($module_name) {
        if(Modulizer_Jar::exists($module_name)) {
            //if its loaded...
            return Modulizer_Jar::get($module_name);
        } else {
            //if not try to call factory function for it
            self::load($module_name);

            if(!Modulizer_Jar::exists($module_name))
                throw new Modulizer_Exception("Requested module ({$module_name}) wasn't loaded, tried call 'loadModule' callbacks still no luck!");

            return Modulizer_Jar::get($module_name);
        }
    }

    public static function load($module_name) {
        $f = &self::$factory;
        foreach(self::$factory->moduleLoaders as $loader)
            call_user_func($loader, $f, $module_name);
    }
}