<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 2014.01.25.
 * Time: 8:57
 */
$vendorDir = dirname(__FILE__);

require_once $vendorDir."/Modulizer_Exception.php";
require_once $vendorDir."/Modulizer_Proto.php";
require_once $vendorDir."/Modulizer_Jar.php";
require_once $vendorDir."/Modulizer_Factory.php";
require_once $vendorDir."/Modulizer_ClassLoader.php";
require_once $vendorDir."/Modulizer_EventHandler.php";

class Modulizer {

    protected static $factory;

    public static function &getFactory() {
        return self::$factory;
    }

    public static function configure($options = array()) {
        $options = $options+array(
                "private_modules_dir" => "private_modules",
                "shared_modules_dir" => "shared_modules"
            );

        self::$factory = new Modulizer_Factory($options);
    }

    public static function register($module_name, $module_callback = null, $dependencies = array(), $lazy = false) {
        $args = func_get_args();
        $args_c = count($args);
        $module_meta = array(
            "name" => null,
            "alias" => null,
            "callback" => null,
            "dependencies" => array(),
            "lazy" => false
        );

        if(is_array($args[0])) {
            $module_meta["name"] = $args[0][0];
            $module_meta["alias"] = $args[0][1];
        } else {
            $module_meta["name"] = $args[0];
        }

        if(is_callable($args[1])) {
            $module_meta["callback"] = $args[1];
            if(($args_c > 2) && is_array($args[2])) {
                $module_meta["dependencies"] = $args[2];
                if(($args_c > 3) && is_bool($args[3]))
                    $module_meta["lazy"] = $args[3];
            } else {
                if(($args_c > 2) && is_bool($args[2])) {
                    $module_meta["lazy"] = $args[2];
                }
            }
        } else {
            if(is_array($args[1])) {
                $module_meta["dependencies"] = $args[1];
                if(($args_c > 2) && is_bool($args[2])) {
                    $module_meta["lazy"] = $args[2];
                }
            } else {
                if(is_bool($args[1])) {
                    $module_meta["lazy"] = $args[1];
                }
            }
        }

        if($module_meta["alias"])
            self::$factory->getJar()->addAlias($module_name['name'], $module_meta['alias']);

        if(count($module_meta['dependencies']))
            foreach($module_meta['dependencies'] as $dep)
                self::load($dep);

        if($module_meta["lazy"]) {
            self::$factory->getJar()->addModule($module_meta['name'], array(
                "__lazy" => true,
                "cb" => $module_meta['callback']
            ));
        } else {
            self::initializeModule($module_meta['name'], $module_meta['callback']);
        }
    }

    private static function &initializeModule($module_name, $cb = null) {
        if(!$cb) {
            $m = self::$factory->getJar()->getModule($module_name);
            $cb = $m['cb'];
        }

        $get = function ($module_name) {
            return Modulizer::get($module_name);
        };
        $f = self::$factory;
        $args = array(&$f, new Modulizer_Proto, $get);
        call_user_func_array($cb, $args);
        self::$factory->getJar()->addModule($module_name, self::$factory->exports, true);
        self::$factory->exports = null;

        return self::$factory->getJar()->getModule($module_name);
    }

    public static function &get($module_name) {
        $exports = self::$factory->getJar()->getModule($module_name);
        if(array_key_exists('__lazy', $exports))
            $exports = self::initializeModule($module_name);
        return $exports;
    }

    public static function load($module_name) {
        if(!self::$factory->getJar()->isLoaded($module_name)) {
            /*load it.. at least include its module.php and chack for if its could be a phar package*/
            $module_name_namespace = explode(".", $module_name);
            array_shift($module_name_namespace);
            array_walk($module_name_namespace, function(&$value, $key) {
                $value = ucfirst($value);
            });
            $module_namespace = implode("\\", $module_name_namespace);
            $dirs = self::$factory->getDirs();
            $loaded = false;
            foreach($dirs as $dir) {
                if(is_dir($dir."/".$module_name)) {
                    //common dir
                    self::$factory->getClassLoader()->addNamespace($module_namespace, $dir."/".$module_name."/src/");
                    require_once $dir."/".$module_name."/module.php";
                    $loaded = true;
                }

                if(file_exists($dir."/".$module_name."phar")) {
                    //phar
                    self::$factory->getClassLoader()->addNamespace($module_namespace, "phar://".$dir."/".$module_name.".phar/src/");
                    require_once "phar://".$dir."/".$module_name.".phar/module.php";
                    $loaded = true;
                }
            }
            if(!$loaded) {
                throw new Modulizer_Exception("Couldnt load module..");
            }
        }
    }
}


function addListener($eventCode, $callback, $priority = 1000) {
    return Modulizer::getFactory()->getEventHandler()->addListener($eventCode, $callback, $priority);
}

function trigger($eventCode, $eventArgs = array()) {
    return Modulizer::getFactory()->getEventHandler()->trigger($eventCode, $eventArgs);
}