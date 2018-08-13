<?php

namespace ElockyAPI;

function autoloader($class) {
    include __DIR__ . '/' . $class . '.class.php';
}

spl_autoload_register('ElockyAPI\autoloader');