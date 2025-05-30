<?php

namespace CRUDAPIGenerator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use CRUDAPIGenerator\API\Request;
use CRUDAPIGenerator\API\Response;
use CRUDAPIGenerator\API\Route;
use CRUDAPIGenerator\API\Router;
use CRUDAPIGenerator\API\Middleware\AuthMiddleware;
use CRUDAPIGenerator\API\Middleware\CorsMiddleware;
use CRUDAPIGenerator\API\Middleware\RateLimitMiddleware;
use CRUDAPIGenerator\API\Middleware\ValidationMiddleware;

class APITest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    public function testRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/test';
        $_GET = ['param' => 'value'];
        $_POST = ['data' => 'test'];
        $_SERVER['HTTP_X_API_KEY'] = 'test-key';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        $request = new Request();

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/api/test', $request->getUri());
        $this->assertEquals('value', $request->getQuery('param'));
        $this->assertEquals('test', $request->getPost('data'));
        $this->assertEquals('test-key', $request->getHeader('X-API-Key'));
        $this->assertEquals('PHPUnit', $request->getUserAgent());
    }

    public function testResponse(): void
    {
        $response = new Response();
        
        // Success response
        $successResponse = $response->success(['id' => 1], 'Record created');
        $this->assertEquals(200, $successResponse->getStatusCode());
        
        // Error response
        $errorResponse = $response->error('Invalid input', 400);
        $this->assertEquals(400, $errorResponse->getStatusCode());
        
        // Not found response
        $notFoundResponse = $response->notFound('Record not found');
        $this->assertEquals(404, $notFoundResponse->getStatusCode());
        
        // Validation error response
        $validationResponse = $response->validationError(['field' => ['Required']]);
        $this->assertEquals(422, $validationResponse->getStatusCode());
    }

    public function testRoute(): void
    {
        $route = new Route('GET', '/api/test/{id}', function($request) {
            return new Response(['id' => $request->getParams()['id']]);
        });

        $this->assertEquals('GET', $route->getMethod());
        $this->assertEquals('/api/test/{id}', $route->getPath());
        $this->assertTrue($route->matches('GET', '/api/test/1'));
        $this->assertFalse($route->matches('POST', '/api/test/1'));
        $this->assertEquals(['id' => '1'], $route->extractParams('/api/test/1'));
    }

    public function testRouter(): void
    {
        $this->router->get('/api/test', function($request) {
            return new Response(['message' => 'Test']);
        });

        $this->router->post('/api/test', function($request) {
            return new Response(['message' => 'Created']);
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/test';
        $request = new Request();
        $response = $this->router->dispatch($request);
        $this->assertEquals(200, $response->getStatusCode());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request();
        $response = $this->router->dispatch($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMiddleware(): void
    {
        // Auth middleware
        $authMiddleware = new AuthMiddleware('test-key');
        $_SERVER['HTTP_X_API_KEY'] = 'test-key';
        $request = new Request();
        $response = $authMiddleware->handle($request);
        $this->assertNull($response);

        $_SERVER['HTTP_X_API_KEY'] = 'invalid-key';
        $request = new Request();
        $response = $authMiddleware->handle($request);
        $this->assertEquals(401, $response->getStatusCode());

        // CORS middleware
        $corsMiddleware = new CorsMiddleware();
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        $request = new Request();
        $response = $corsMiddleware->handle($request);
        $this->assertNull($response);

        // Rate limit middleware
        $rateLimitMiddleware = new RateLimitMiddleware(1, 60);
        $request = new Request();
        $response = $rateLimitMiddleware->handle($request);
        $this->assertNull($response);

        // İkinci istek rate limit'e takılmalı
        $response = $rateLimitMiddleware->handle($request);
        $this->assertEquals(429, $response->getStatusCode());

        // Validation middleware
        $validationMiddleware = new ValidationMiddleware([
            'name' => 'required|min:3',
            'email' => 'required|email'
        ]);

        $_POST = [
            'name' => 'John',
            'email' => 'invalid-email'
        ];
        $request = new Request();
        $response = $validationMiddleware->handle($request);
        $this->assertEquals(422, $response->getStatusCode());
    }
} 