<?php

/**
 * A class for store items.
 * @author Daniel Simon, Tamás Szabó
 */
class Bag {
    
    //
    static $modules = array();
    static $aliases = array();
    
    public static function resolve($name)
    {
        $name = strtolower($name);
        
        if(array_key_exists($name, self::$aliases))
            return self::$aliases[$name];
        return $name;
    }
    
    public static function &get($module_name) {
        if(array_key_exists($module_name, self::$modules))
            return self::$modules[$module_name];
            
       return null;
    }
    
    public static function &byAlias($alias) {
        $module_name = self::resolve($alias);
        
        if(array_key_exists($module_name, self::$modules))
            return self::$modules[$module_name];
            
        return null;
    }
    
    public static function set($module_name, $exports) {
        $module_name = strtolower($module_name);
        
        if(array_key_exists($module_name, self::$modules))
            throw new LoaderException("Module with name {$module} already exists!");
            
        self::$modules[$module_name] = $exports;
    }
    
    public static function alias($alias, $module_name) {
        self::$aliases[strtolower($alias)] = strtolower($module_name);
    }
}