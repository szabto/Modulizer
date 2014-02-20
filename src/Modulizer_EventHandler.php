<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 2014.02.05.
 * Time: 12:57
 */
define('EVENT_SIGNAL_TERMINATE', 0x01);

class Modulizer_EventHandler {

    protected $listeners = array();

    public function addListener($code, $cb, $priority = 1000) {
        if(!array_key_exists($code, $this->listeners))
            $this->listeners[$code] = array();
        if(!array_key_exists($priority, $this->listeners[$code]))
            $this->listeners[$code][$priority] = array();
        $this->listeners[$code][$priority][] = $cb;
        ksort($this->listeners[$code],SORT_NUMERIC);
    }

    public function trigger($code, $args = array()) {
        $signals = array();
        $f = Modulizer::getFactory();
        $args = array(&$f, $args);
        if(array_key_exists($code, $this->listeners))
            foreach($this->listeners[$code] as $priorities) {
                foreach($priorities as $callback) {
                    $signals[] = $last_signal = call_user_func_array($callback, $args);
                    if(is_array($last_signal) && array_key_exists('signals', $last_signal)) {
                        if(($last_signal['signals'] & EVENT_SIGNAL_TERMINATE) == 1)
                            break 2;
                    }
                }
            }
        return $signals;
    }
} 