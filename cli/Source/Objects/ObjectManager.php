<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source\Objects;

use Source\ErrorHandler;

class ObjectManager
{

    private $injector;
    private $errorHandler;

    public function __construct()
    {
        $this->injector = new DependencyInjector();
        $this->errorHandler = new ErrorHandler();
    }

    /**
     * @return object Singleton of given class
     */
    public function getSingleton(string $class)
    {
        try {
            return $this->injector->inject($class);
        } catch (\Exception $e) {
            $this->errorHandler->handle($e);
            exit;
        }
    }

    /**
     * @return object A new instance of given class
     */
    public function createObject(string $class)
    {
        try {
            return $this->injector->inject($class, true);
        } catch (\Exception $e) {
            $this->errorHandler->handle($e);
            exit;
        }
    }

}
