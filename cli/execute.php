#!/usr/bin/php
<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 *
 * `php cli/execute.php <command> <args>`
 * This file executes your desired command
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    echo 'Could not start the CLI. Your PHP version must be 7 or higher. Your current version: ' . PHP_VERSION . PHP_EOL . PHP_EOL;
    exit();
} elseif (PHP_SAPI !== 'cli') {
    echo 'Could not start the CLI. It must be run as CLI application.' . PHP_EOL . PHP_EOL;
    exit();
}

define('BASE', str_replace('\\', '/', dirname(__DIR__)));

spl_autoload_register(function (string $class) {
    $adjust = str_replace('\\', '/', $class);
    $file = BASE . '/cli/' . $adjust . '.php';
    require_once $file;
});

require_once "Source/Component/DependencyInjector.php";

$injector = new \Source\Component\DependencyInjector();

function inject(string $class, bool $create = false)
{
    global $injector;
    try {
        return $injector->inject($class, $create);
    } catch (\Exception $e) {
        echo $e->getMessage();
        exit();
    }

}

try {
    unset($argv[0]);
    /** @var \Source\App $executor */
    $executor = inject('Source\App');
    $executor->execute($argv);
} catch (Exception $e) {
    echo PHP_EOL . PHP_EOL . $e->getMessage() . PHP_EOL . PHP_EOL;
    exit();
}

/**
 * todo...
 *
 * Allow aliases to be combined
 * Examples:
 * -hd === -h -d
 * -hdq === -h -d -q
 * 2nd example will actually cause an error because -d and -q exclude each other, but anyway you got the idea
 *
 * todo...
 *
 * Some sort of data buffer that stores all input, output and calculated / processed data in case an error occurs. If
 * an error occurs, all the data will be dumped to the error.log
 *
 * todo...
 *
 * Some sort of OutputProcessObject that will display some process bar to the user
 * => General output objects, for example process bar, an confirmation window and so on and so on...
 *
 * todo...
 *
 * Example:
 * Clears the error log file
 *
 * LastError (requires the data buffer):
 * This command will output information about the last error that occurred and has been stored in the error.log
 */
