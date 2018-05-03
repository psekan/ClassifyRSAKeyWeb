<?php

require __DIR__ . '/../vendor/autoload.php';

const TEMPLATES_DIR = __DIR__ . '/templates';
const CONFIG_DIR = __DIR__ . '/config';

$configurator = new Nette\Configurator;

$configurator->enableTracy(__DIR__ . '/../log');

$configurator->setTimeZone('Europe/Prague');
$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

return $container;
