<?php
/**
* @author      Roland Schilffarth <roland@schilffarth.org>
* @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
*/

namespace Source;

class ErrorHandler
{

    public function handle(\Exception $e)
    {
        echo PHP_EOL . PHP_EOL
            . $e->getMessage() . PHP_EOL . PHP_EOL
            . $e->getTraceAsString() . PHP_EOL . PHP_EOL;
    }

}
