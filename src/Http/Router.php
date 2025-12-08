<?php

declare(strict_types=1);

namespace App\Http;

use Closure;
use InvalidArgumentException;

final class Router
{
    /**
     * @var array<string, array<string, Closure>>
     */
    private array $routes = [];

    /**
     * Register a route handler.
     *
     * @return void
     */
    public function add(string $method, string $path, Closure $handler): void
    {
        $method = strtoupper($method);
        $this->routes[$method][$path] = $handler;
    }

    /**
     * Dispatch to a matching route.
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function dispatch(string $method, string $path): mixed
    {
        $method = strtoupper($method);
        if (!isset($this->routes[$method][$path])) {
            throw new InvalidArgumentException("Route {$method} {$path} not found");
        }
        return ($this->routes[$method][$path])();
    }
}

