<?php

namespace CRUDAPIGenerator\API;

class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $headers;
    private array $files;
    private string $method;
    private string $uri;
    private array $params;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->headers = getallheaders();
        $this->files = $_FILES;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->params = [];
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getQuery(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }

        return $this->get[$key] ?? $default;
    }

    public function getPost(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }

        return $this->post[$key] ?? $default;
    }

    public function getHeader(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }

        return $this->headers[$key] ?? $default;
    }

    public function getFile(string $key = null)
    {
        if ($key === null) {
            return $this->files;
        }

        return $this->files[$key] ?? null;
    }

    public function getJson(): ?array
    {
        $json = file_get_contents('php://input');
        if (empty($json)) {
            return null;
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }

    public function isAjax(): bool
    {
        return isset($this->headers['X-Requested-With']) && 
               strtolower($this->headers['X-Requested-With']) === 'xmlhttprequest';
    }

    public function isJson(): bool
    {
        return isset($this->headers['Content-Type']) && 
               strpos($this->headers['Content-Type'], 'application/json') !== false;
    }

    public function getClientIp(): string
    {
        $ip = '';
        
        if (isset($this->server['HTTP_CLIENT_IP'])) {
            $ip = $this->server['HTTP_CLIENT_IP'];
        } elseif (isset($this->server['HTTP_X_FORWARDED_FOR'])) {
            $ip = $this->server['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($this->server['REMOTE_ADDR'])) {
            $ip = $this->server['REMOTE_ADDR'];
        }

        return $ip;
    }

    public function getUserAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }
} 