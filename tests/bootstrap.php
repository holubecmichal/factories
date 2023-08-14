<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Tester\Environment;

Environment::setup();

$configurator = new Nette\Configurator();

$configurator->setDebugMode(false);

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->addConfig(__DIR__ . '/tests.neon');

return $configurator->createContainer();
