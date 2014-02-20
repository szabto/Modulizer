<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 2014.02.05.
 * Time: 9:58
 */

Modulizer::register("com.test.module", function($f, $e, $g) {
    echo "its me.. the test module<br>";
    new Test\Module\Gas;
    trigger('module_loaded', array('com.test.module'));
}, true);