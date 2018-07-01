<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source\Component\Argument;

use Source\Command\AbstractCommand;

/**
 * Simple arguments represent levers and present a boolean as value - Whether they have been passed or not
 *
 * Example usages @see AbstractCommand::setDefaultArgs
 * --debug  -d      Enables verbose output
 * --help   -h      Triggers the help message for the run command
 */
class Simple extends AbstractArgumentObject
{

    public function launch(array &$argv) {}

}
