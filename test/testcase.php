<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 2014.01.25.
 * Time: 9:01
 */

require "phar://../build/Modulizer.phar";

Modulizer::configure(array(
    "private_modules_dir" => __DIR__."/private_modules",
    "shared_modules_dir" => __DIR__."/shared_modules"
));

Modulizer::register("main", function(&$f, $e, $g) {
   echo "hello<br>";
   new Test\Module\Gas;
    $f->getEventHandler()->addListener('module_loaded', function(&$f, $args) {
       echo "Event fired: module_loaded, module is loaded: {$args[0]}";

   });
   $g('com.test.module');
}, array("com.test.module"));