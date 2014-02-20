<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 2014.01.25.
 * Time: 8:57
 */

class Modulizer_Jar {

    protected $module_aliases = array();
    protected $modules = array();

    public function addModule($module, $exports, $inited = false) {
        if(array_key_exists(strtolower($module), $this->modules) && !$inited)
            throw new Modulizer_Exception("Module with name: {$module} has already been loaded!");

        $this->modules[strtolower($module)] = $exports;
    }

    public function isLoaded($module) {
        return (bool)array_key_exists(strtolower($module), $this->modules);
    }

    public function fetchAlias($alias) {
        if(array_key_exists($alias, $this->module_aliases)) {
            return $this->module_aliases[$alias];
        }
        return false;
    }

    public function addAlias($module, $alias) {
        $module = strtolower($module);
        $this->module_aliases[$alias] = $module;
    }

    public function &getModule($module) {
        if(($module_name = $this->fetchAlias($module)) != false)
            $module = $module_name;
        if(array_key_exists(strtolower($module), $this->modules))
            return $this->modules[strtolower($module)];

        throw new Modulizer_Exception("Module wasnt loaded!");
    }
} 