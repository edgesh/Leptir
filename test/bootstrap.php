<?php
error_reporting(E_ALL | E_STRICT);
define('ROOT_TEST', dirname(__DIR__));

$dir = __DIR__;
$previousDir = '.';
while (!is_file($dir . '/init_autoloader.php')) {
    $dir = dirname($dir);
    if ($previousDir === $dir) {
        throw new RuntimeException('Woops - can\'t find project root folder.');
    }
    $previousDir = $dir;
}
chdir($dir); // it's gonna be easier this way

require 'init_autoloader.php';
require __DIR__ . '/TestConfiguration.php';

LeptirTest\TestConfiguration::init();
