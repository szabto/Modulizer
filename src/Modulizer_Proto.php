<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 2014.01.25.
 * Time: 9:04
 */

class Modulizer_Proto {
    public function __call($fn, $args) {
        return call_user_func_array($this->{$fn}, $args);
    }
} 