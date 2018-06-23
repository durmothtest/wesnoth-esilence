<?php
namespace Source;

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
    public function inject(string $class)
    {
        if (isset($this->loaded[$class])) {
            return $this->loaded[$class];
        }
        $reflector = new \ReflectionClass($class);
        if (!$reflector->isInstantiable()) {
            throw new \Exception("$class is not instantiable.");
        }
        $return = $this->injectConstructorArgs($reflector, $class);
        return $this->getClass($class, $return);
    }

    /**
     * Instantiate new constructor params
     */
    private function injectConstructorArgs(\ReflectionClass $reflector, $class)
    {
        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            return $this->getClass($class);
        }
        $parameters = $constructor->getParameters();
        $dependencies = $this->getDependencies($parameters);
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * If class has already been instantiated, return its singleton
     * Otherwise create new object and return its instance
     */
    private function getClass(string $class, $instance = null)
    {
        while (strlen($class) && $class[0] === "\\") {
            $class = substr($class, 1);
        }
        if (!isset($this->loaded[$class])) {
            $toInject = $instance === null ? new $class : $instance;
            $this->loaded[$class] = $toInject;
        }
        return $this->loaded[$class];
    }

    /**
     * Build up a list of dependencies for given parameters (of constructor)
     */
    private function getDependencies(array $parameters): array
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
                if (!isset($this->loaded[$dependencyName])) {
                    $instance = $this->inject($dependencyName);
                }
                $dependencies[] = $this->getClass($dependencyName, $instance);
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
        throw new \Exception("(\Core\Resource\Helper\GlobalHelper\Autoloader\DependencyInjector)->injectNonClass(): Failed to inject $parameter");
    }

}
