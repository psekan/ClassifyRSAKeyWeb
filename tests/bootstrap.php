<?php

use Nette\Configurator;
use Tester\Environment;

require __DIR__ . '/../vendor/autoload.php';

$debugMode = true;
const APP_DIR = __DIR__ .'/../app';
const TEMPLATES_DIR = APP_DIR . '/templates';
const CONFIG_DIR = APP_DIR . '/config';

Environment::setup();

$configurator = new Configurator;
//$configurator->setDebugMode(false);
$configurator->enableDebugger(__DIR__ . '/../log');
$configurator->setTimeZone('Europe/Prague');
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
    ->addDirectory(APP_DIR)
    ->register();
$configurator->addParameters([
    'appDir' => __DIR__ . '/../app',
    'wwwDir' => __DIR__ . '/../www',
    'debugMode' => $debugMode,
    'productionMode' => !$debugMode,
    'environment' => $debugMode ? 'development' : 'production'
]);
$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
$configurator->addConfig(__DIR__ . '/../app/config/config.local.neon');

return $configurator->createContainer();