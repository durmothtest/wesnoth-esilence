<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source\Component;

/**
 * Automatic constructor dependency injection.
 */
class DependencyInjector
{

    /**
     * Property $loaded used to store singleton instances
     */
    private $loaded = [];

    /**
     * Build an instance of the given class
     */
    public function inject(string $class, bool $create = false)
    {
        if (isset($this->loaded[$class]) && !$create) {
            return $this->loaded[$class];
        }
        $reflector = new \ReflectionClass($class);
        if (!$reflector->isInstantiable()) {
            throw new \Exception("$class is not instantiable.");
        }
        $return = $this->injectConstructorArgs($reflector, $class, $create);
        return $this->getClass($class, $return, $create);
    }

    /**
     * Instantiate new constructor params
     */
    private function injectConstructorArgs(\ReflectionClass $reflector, string $class, bool $create)
    {
        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            return $this->getClass($class, null, $create);
        }
        $parameters = $constructor->getParameters();
        $dependencies = $this->getDependencies($parameters, $create);
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * If class has already been instantiated, return its singleton
     * Otherwise create new object and return its instance
     * if $create is true, always create new object
     */
    private function getClass(string $class, $instance = null, bool $create)
    {
        while (strlen($class) && $class[0] === "\\") {
            $class = substr($class, 1);
        }
        if (!isset($this->loaded[$class]) || $create) {
            $toInject = $instance === null ? new $class : $instance;
            if ($create) {
                return $toInject;
            } else {
                $this->loaded[$class] = $toInject;
            }
        }
        return $this->loaded[$class];
    }

    /**
     * Build up a list of dependencies for given parameters (of constructor)
     */
    private function getDependencies(array $parameters, bool $create): array
    {
        $dependencies = [];
        /** @var \ReflectionParameter $param */
        foreach ($parameters as $param) {
            $dependency = $param->getClass();
            if ($dependency === null) {
                $dependencies[] = $this->injectNonClass($param);
            } else {
                $instance = null;
                $dependencyName = $dependency->name;
                if (!isset($this->loaded[$dependencyName]) || $create) {
                    $instance = $this->inject($dependencyName, $create);
                }
                $dependencies[] = $this->getClass($dependencyName, $instance, $create);
            }
        }
        return $dependencies;
    }

    /**
     * Determine what to do with a non-class value
     */
    private function injectNonClass(\ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        throw new \Exception(sprintf("(\Core\Resource\Helper\GlobalHelper\Autoloader\DependencyInjector)->injectNonClass(): Failed to inject %s", $parameter));
    }

}
