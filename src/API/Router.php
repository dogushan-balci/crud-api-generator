<?php

namespace CRUDAPIGenerator\API;

class Router
{
    private array $routes = [];
    private array $globalMiddlewares = [];

    public function addRoute(string $method, string $path, $handler): Route
    {
        $route = new Route($method, $path, $handler);
        $this->routes[] = $route;
        return $route;
    }

    public function get(string $path, $handler): Route
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, $handler): Route
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, $handler): Route
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, $handler): Route
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function addGlobalMiddleware($middleware): self
    {
        $this->globalMiddlewares[] = $middleware;
        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getUri();

        foreach ($this->routes as $route) {
            if ($route->matches($method, $path)) {
                $params = $route->extractParams($path);
                $request->setParams($params);

                // Global middleware'leri çalıştır
                foreach ($this->globalMiddlewares as $middleware) {
                    $response = $this->executeMiddleware($middleware, $request);
                    if ($response instanceof Response) {
                        return $response;
                    }
                }

                // Route middleware'lerini çalıştır
                foreach ($route->getMiddlewares() as $middleware) {
                    $response = $this->executeMiddleware($middleware, $request);
                    if ($response instanceof Response) {
                        return $response;
                    }
                }

                // Handler'ı çalıştır
                return $this->executeHandler($route->getHandler(), $request);
            }
        }

        // Route bulunamadı
        return (new Response())->notFound('Route not found');
    }

    private function executeMiddleware($middleware, Request $request)
    {
        if (is_callable($middleware)) {
            return $middleware($request);
        }

        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            if (method_exists($instance, 'handle')) {
                return $instance->handle($request);
            }
        }

        throw new \RuntimeException('Invalid middleware');
    }

    private function executeHandler($handler, Request $request): Response
    {
        if (is_callable($handler)) {
            $response = $handler($request);
        } elseif (is_string($handler) && class_exists($handler)) {
            $instance = new $handler();
            if (method_exists($instance, 'handle')) {
                $response = $instance->handle($request);
            } else {
                throw new \RuntimeException('Handler class must have a handle method');
            }
        } else {
            throw new \RuntimeException('Invalid handler');
        }

        if (!$response instanceof Response) {
            $response = new Response($response);
        }

        return $response;
    }
} 