<?php

use Symfony\Component\Console\Input\InputOption;

try {
    //Check if composer dependencies were initialized
    if (!file_exists(__DIR__ . '/vendor')) {
        echo "Initialization of the application." . PHP_EOL;

        //Check if composer exists
        if (!file_exists(__DIR__ . '/composer.phar')) {
            echo "Downloading composer." . PHP_EOL;
            $ret = file_put_contents(__DIR__ . "/composer.phar", fopen("https://getcomposer.org/composer.phar", 'r'));
            if ($ret === false) {
                throw new Exception('Cannot download composer.phar file.');
            }
        }

        //Try to run composer
        echo "Running composer for dependencies." . PHP_EOL;
        $ret = system('php composer.phar install');
        if ($ret === false) {
            throw new Exception('Composer failed.');
        }
        echo PHP_EOL . PHP_EOL;
    }
}
catch (Exception $ex) {
    die('Cannot initialize application: '. $ex->getMessage());
}

$container = require __DIR__ . '/app/bootstrap.php';

/** @var \Symfony\Component\Console\Application $console */
$console = $container->getByType(\Symfony\Component\Console\Application::class);
$console->setName('ClassifyRSAKey CLI');
$console->getDefinition()->setOptions([
    new InputOption('--ansi', '', InputOption::VALUE_NONE, 'Force ANSI output')
]);
exit($console->run());
