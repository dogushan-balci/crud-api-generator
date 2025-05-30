<?php

namespace CRUDAPIGenerator\API;

abstract class Middleware
{
    abstract public function handle(Request $request);
}

class AuthMiddleware extends Middleware
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function handle(Request $request)
    {
        $apiKey = $request->getHeader('X-API-Key');
        
        if (!$apiKey || $apiKey !== $this->apiKey) {
            return (new Response())->unauthorized('Invalid API key');
        }

        return null;
    }
}

class CorsMiddleware extends Middleware
{
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;

    public function __construct(
        array $allowedOrigins = ['*'],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'X-API-Key']
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
    }

    public function handle(Request $request)
    {
        $origin = $request->getHeader('Origin');

        if ($origin) {
            if (in_array('*', $this->allowedOrigins) || in_array($origin, $this->allowedOrigins)) {
                header("Access-Control-Allow-Origin: $origin");
            }
        }

        header('Access-Control-Allow-Methods: ' . implode(', ', $this->allowedMethods));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->allowedHeaders));
        header('Access-Control-Max-Age: 86400');

        if ($request->isMethod('OPTIONS')) {
            return new Response(null, 204);
        }

        return null;
    }
}

class RateLimitMiddleware extends Middleware
{
    private int $maxRequests;
    private int $windowSeconds;
    private string $cacheDir;

    public function __construct(int $maxRequests = 60, int $windowSeconds = 60, string $cacheDir = null)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir();
    }

    public function handle(Request $request)
    {
        $ip = $request->getClientIp();
        $cacheFile = $this->cacheDir . '/rate_limit_' . md5($ip) . '.json';

        $data = $this->getCacheData($cacheFile);
        $now = time();

        // Eski istekleri temizle
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now) {
            return $timestamp > ($now - $this->windowSeconds);
        });

        // Yeni isteği ekle
        $data['requests'][] = $now;

        // İstek sayısını kontrol et
        if (count($data['requests']) > $this->maxRequests) {
            $this->saveCacheData($cacheFile, $data);
            return (new Response())->error('Too many requests', 429);
        }

        $this->saveCacheData($cacheFile, $data);
        return null;
    }

    private function getCacheData(string $cacheFile): array
    {
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if (is_array($data)) {
                return $data;
            }
        }

        return ['requests' => []];
    }

    private function saveCacheData(string $cacheFile, array $data): void
    {
        file_put_contents($cacheFile, json_encode($data));
    }
}

class ValidationMiddleware extends Middleware
{
    private array $rules;

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    public function handle(Request $request)
    {
        $data = $request->isJson() ? $request->getJson() : $request->getPost();
        $errors = [];

        foreach ($this->rules as $field => $rule) {
            $value = $data[$field] ?? null;

            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field][] = 'Field is required';
                continue;
            }

            if (!empty($value)) {
                if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = 'Invalid email format';
                }

                if (strpos($rule, 'numeric') !== false && !is_numeric($value)) {
                    $errors[$field][] = 'Field must be numeric';
                }

                if (preg_match('/min:(\d+)/', $rule, $matches)) {
                    $min = (int) $matches[1];
                    if (strlen($value) < $min) {
                        $errors[$field][] = "Field must be at least {$min} characters";
                    }
                }

                if (preg_match('/max:(\d+)/', $rule, $matches)) {
                    $max = (int) $matches[1];
                    if (strlen($value) > $max) {
                        $errors[$field][] = "Field must not exceed {$max} characters";
                    }
                }
            }
        }

        if (!empty($errors)) {
            return (new Response())->validationError($errors);
        }

        return null;
    }
} 