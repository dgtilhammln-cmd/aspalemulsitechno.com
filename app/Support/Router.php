<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * MUNG CMS V1
 * File: app/Support/Router.php
 * Author: Firmanda Himawan
 * Description: Lightweight routing layer powering the MUNG CMS front controller.
 */

final class Router
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $routes = [];

    public function get(string $pattern, callable|array|string $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable|array|string $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    public function addRoute(string $method, string $pattern, callable|array|string $handler): void
    {
        $method = strtoupper($method);
        $pattern = '/' . ltrim($pattern, '/');

        [$regex, $parameters] = $this->compilePattern($pattern);

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'regex' => $regex,
            'parameters' => $parameters,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $method = strtoupper($method);
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $candidates = $this->routes[$method] ?? [];

        foreach ($candidates as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $params = [];
            foreach ($route['parameters'] as $param) {
                $params[$param] = $matches[$param] ?? null;
            }

            $handler = $this->resolveHandler($route['handler']);

            return $handler(...array_values($params));
        }

        abort(404);

        return null;
    }

    /**
     * @return array{0:string,1:array<int,string>}
     */
    private function compilePattern(string $pattern): array
    {
        $parameterNames = [];

        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $matches) use (&$parameterNames): string {
            $parameterNames[] = $matches[1];

            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $pattern);

        if ($regex === null) {
            throw new InvalidArgumentException('Route pattern tidak valid: ' . $pattern);
        }

        $regex = '#^' . $regex . '$#u';

        return [$regex, $parameterNames];
    }

    private function resolveHandler(callable|array|string $handler): callable
    {
        if ($handler instanceof Closure) {
            return $handler;
        }

        if (is_string($handler) && function_exists($handler)) {
            return $handler;
        }

        if (is_array($handler) && is_callable($handler)) {
            return $handler;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            if (is_string($class) && is_string($method)) {
                return $this->instantiateController($class, $method);
            }
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);

            return $this->instantiateController($class, $method);
        }

        throw new RuntimeException('Handler route tidak dapat di-resolve.');
    }

    private function instantiateController(string $class, string $method): callable
    {
        if (!class_exists($class)) {
            throw new RuntimeException(sprintf('Controller %s tidak ditemukan.', $class));
        }

        $controller = new $class(app_pdo());

        if (!method_exists($controller, $method)) {
            throw new RuntimeException(sprintf('Method %s::%s tidak tersedia.', $class, $method));
        }

        return [$controller, $method];
    }
}
