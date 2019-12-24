<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Console;

use ReflectionClass;
use ReflectionNamedType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class SocoCommandLoader implements CommandLoaderInterface
{
    private array $commands;

    public function __construct(array $commands = []) {
        $this->commands = $commands;
    }

    public function get(string $name)
    {
        if (!$this->has($name)) {
            throw new CommandNotFoundException("Command $name not found");
        }
        if (!is_a($this->commands[$name], Command::class, true)) {
            throw new CommandNotFoundException("{$this->commands[$name]} is not a Command");
        }
        return $this->inject($this->commands[$name]);
    }

    public function has(string $name)
    {
        return key_exists($name, $this->commands);
    }

    public function getNames()
    {
        return array_keys($this->commands);
    }

    protected function inject(string $classname, array $parameters = []): object
    {
        echo "\nInject $classname";
        $classReflection = new ReflectionClass($classname);
        $method = $classReflection->getConstructor();
        $args = [];

        if ($method !== null) {
            foreach ($method->getParameters() as $index => $parameter) {
                $paramName = $parameter->getName();
                $paramClass = $parameter->getClass();
                if (array_key_exists($paramName, $parameters)) {
                    $value = &$parameters[$paramName];
                } elseif ($parameter->isDefaultValueAvailable() || $parameter->isOptional()) {
                    $args[] = null;
                } elseif ($paramClass !== null) {
                    $args[] = $this->inject($paramClass->getName());
                } else {
                    throw new \Exception("Parameter \${$paramName} of the {$classname} could not be resolved");
                }
                $args[] = &$value;
            }
        }

        return new $classname(...$args);
    }
}