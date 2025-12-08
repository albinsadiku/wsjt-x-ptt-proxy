<?php

declare(strict_types=1);

namespace App\Container;

use Closure;
use InvalidArgumentException;

final class Container
{
    /**
     * @var array<string, Closure(self): object>
     */
    private array $bindings = [];

    /**
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Register a factory binding.
     *
     * @return void
     */
    public function set(string $id, Closure $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    /**
     * Register a singleton binding.
     *
     * @return void
     */
    public function singleton(string $id, Closure $factory): void
    {
        $this->set($id, function (self $container) use ($id, $factory): object {
            if (!isset($this->instances[$id])) {
                $this->instances[$id] = $factory($container);
            }
            return $this->instances[$id];
        });
    }

    /**
     * Resolve a service by id.
     *
     * @param string $id
     * @return object
     * @throws InvalidArgumentException
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        if (!isset($this->bindings[$id])) {
            throw new InvalidArgumentException("Service {$id} not registered.");
        }
        return ($this->bindings[$id])($this);
    }
}

