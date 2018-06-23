#!/usr/bin/php
<?php
/**
 * `php cli/execute.php <command> <args>`
 *
 * This file executes your desired command
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    echo 'Could not start Eternal Silence CLI. Your PHP version must be 7 or higher. Your current version: ' . PHP_VERSION . PHP_EOL . PHP_EOL;
    exit();
} elseif (PHP_SAPI !== 'cli') {
    echo 'Could not start Eternal Silence CLI. It must be run as CLI application.' . PHP_EOL . PHP_EOL;
    exit();
}

define('BASE', str_replace('\\', '/', dirname(__DIR__)));

spl_autoload_register(function (string $class) {
    $adjust = str_replace('\\', '/', $class);
    $file = BASE . '/cli/' . $adjust . '.php';
    require_once $file;
});

require_once "Source/DependencyInjector.php";

$injector = new \Source\DependencyInjector();

function inject(string $class)
{
    global $injector;
    try {
        return $injector->inject($class);
    } catch (\Exception $e) {
        echo $e->getMessage();
        exit();
    }

}

try {
    unset($argv[0]);
    /** @var \Source\Run $executor */
    $executor = inject('Source\Run');
    $executor->execute($argv);
} catch (Exception $e) {
    echo PHP_EOL . PHP_EOL . $e->getMessage() . PHP_EOL . PHP_EOL;
    exit();
}
