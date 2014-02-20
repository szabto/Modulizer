<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 2014.01.25.
 * Time: 8:57
 */

class Modulizer_Factory extends Modulizer_Proto {

    protected $__configuration;
    protected $__classLoader;
    protected $__jar;
    protected $__eventHandler;

    public $exports;

    public function __construct($options = array()) {
        $this->__classLoader = new Modulizer_ClassLoader();
        $this->__jar = new Modulizer_Jar();
        $this->__eventHandler = new Modulizer_EventHandler();
        $this->__configuration = $options;
    }

    public function &getJar() {
        return $this->__jar;
    }

    public function getDirs() {
        return array($this->__configuration['private_modules_dir'], $this->__configuration['shared_modules_dir']);
    }

    public function &getClassLoader() {
        return $this->__classLoader;
    }

    public function &getEventHandler() {
        return $this->__eventHandler;
    }
} 