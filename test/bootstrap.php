<?php
error_reporting(E_ALL | E_STRICT);
define('ROOT_TEST', dirname(__DIR__));

require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/TestConfiguration.php';

LeptirTest\TestConfiguration::init();
