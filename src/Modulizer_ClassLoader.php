<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 2014.01.25.
 * Time: 8:58
 */

class Modulizer_ClassLoader {

    protected $namespaces = array();

    public function __construct() {
        spl_autoload_register(array(&$this, "autoload"));
    }

    public function addNamespace($namespace, $path) {
        if(array_key_exists($namespace, $this->namespaces))
            $this->namespaces[$namespace][] = $path;
        else
            $this->namespaces[$namespace] = array($path);
    }

    public function autoload($class_name) {
        foreach($this->namespaces as $namespace => $paths) {
            $class_file = str_replace(array($namespace."\\", "\\"), array("", "/"), $class_name);
            if(strpos($class_name, $namespace) !== FALSE) {
                foreach($paths as $path) {
                    if(file_exists($path.$class_file.".php")) {
                        require_once $path.$class_file.".php";
                        return;
                    }
                }
            }
        }
    }
} 