<?php

/**
 * Development router for PHP's built-in web server.
 *
 * Usage: php -S localhost:8080 -t public bin/router.php
 *
 * Defines the DEV_SERVER constant so templates can inject the live-reload
 * script, then delegates to the DevRouter class.
 */

define('DEV_SERVER', true);

require __DIR__ . '/../vendor/autoload.php';

if ((new \Leaf\DevRouter(__DIR__ . '/..'))->handle() === false) {
    return false;
}
