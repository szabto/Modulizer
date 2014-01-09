<?php
require_once "Bag.php";

class LoaderException Extends Exception {};

class proto {
	public function __call($fn, $args) {
		return call_user_func_array($this->{$fn}, $args);
	}
}

class Modulizer {
    public static function register( /* $name, $alias, $dependencies, $fnc = null, $comfort = 0 */ ) {
        $args = func_get_args();
        $argsC = func_num_args();
        
        if( $argsC < 3 ) {
            throw new LoaderException("Too few arguments!");
        }
        
        $name = $args[0];
        $comfort = 0;
        if( is_array($args[1]) ) {
            $alias = null;
            $dependecies = $args[1];
            $fnc = $args[2];
            if( $argsC == 4 )
                $comfort = $args[3];
        }
        else {
            $alias = $args[1];
            $dependencies = $args[2];
            $fnc = $args[3];
            if( $argC == 5 )
                $comfort = $args[4];
        }

        //Check if this module already registered
    	/*if( Bag::get($name) || (!is_array($alias) && Bag::byAlias($alias)) ) {
    		if( (array_key_exists($name, $bag) && $bag[$name]["loaded"]) || (!is_Array($alias) && array_key_exists($alias, $bag) && $bag[$alias]["loaded"]) )
    			throw new LoaderException("Registered an existing module ({$name})");
    	}*/
    
        //Check all dependencies are loaded
        if( $depCount = count($dependencies) ) {
        	for($i=0;$i<$depCount;$i++) {
        		if( !Bag::get($dependencies[$i]) ) {
        			throw new LoaderException("Call a module with unresolved dependencies");
        		}
        	}
        }
        
    	$factory = new stdclass;
    	if( $fnc && !$comfort ) {
    		$fnc($factory, function($n) { return req($n);}, new proto);
    	}
    	
    	//Register module loaded
    	Bag::alias($name, $name);
    	if( $alias ) {
    		Bag::alias($alias, $name);
    	}
    	
    	if( $comfort ) {
    		$e = new proto;
    		Bag::set($name, array(
    			"exports" => $e ,
    			"loaded" => false,
    			"a" => $name,
    			"b" => $dependencies,
    			"c" => $fnc
    		));
    		return;
    	}

    	$exports = null;
    	if( isset($factory->exports) )
    		$exports = $factory->exports;
    
    	Bag::set($name, array(
    		"exports" => $exports,
    		"dependencies" => $dependencies,
    		"loaded" => true
    	));
    }

    function get($name, $instance = false) {
    	if(!$exp = Bag::get($name))
    		if(!$exp = Bag::byAlias($name)) 
    		    throw new LoaderException("Requiring an unknown module.");
    
    	if( $exp ) {
    		if( isset($exp["loaded"]) && !$exp["loaded"] ) {
    			Modulizer::register($exp["a"], $exp["b"], $exp["c"]);
    			$exp["exports"] = Modulizer::get($name, $instance);
    		}
    	}
		$e = $exp["exports"];
		return $e;
    }
}
