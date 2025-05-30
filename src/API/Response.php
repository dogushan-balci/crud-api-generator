<?php

namespace CRUDAPIGenerator\API;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private $content;

    public function __construct($content = null, int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = array_merge([
            'Content-Type' => 'application/json'
        ], $headers);
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setContent($content): self
    {
        $this->content = $content;
        return $this;
    }

    public function json(array $data, int $statusCode = 200): self
    {
        $this->content = $data;
        $this->statusCode = $statusCode;
        $this->headers['Content-Type'] = 'application/json';
        return $this;
    }

    public function success($data = null, string $message = 'Success'): self
    {
        return $this->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
    }

    public function error(string $message, int $statusCode = 400, $data = null): self
    {
        return $this->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public function notFound(string $message = 'Not Found'): self
    {
        return $this->error($message, 404);
    }

    public function unauthorized(string $message = 'Unauthorized'): self
    {
        return $this->error($message, 401);
    }

    public function forbidden(string $message = 'Forbidden'): self
    {
        return $this->error($message, 403);
    }

    public function validationError(array $errors): self
    {
        return $this->error('Validation Error', 422, $errors);
    }

    public function send(): void
    {
        // Status code'u ayarla
        http_response_code($this->statusCode);

        // Header'ları ayarla
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // İçeriği gönder
        if (is_array($this->content)) {
            echo json_encode($this->content, JSON_UNESCAPED_UNICODE);
        } else {
            echo $this->content;
        }
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        $response = new self();
        $response->setHeader('Location', $url);
        $response->setStatusCode($statusCode);
        return $response;
    }

    public static function download(string $filePath, string $fileName = null): self
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException('File not found');
        }

        $fileName = $fileName ?? basename($filePath);
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath);

        $response = new self();
        $response->setHeader('Content-Type', $mimeType);
        $response->setHeader('Content-Disposition', "attachment; filename=\"$fileName\"");
        $response->setHeader('Content-Length', $fileSize);
        $response->setHeader('Cache-Control', 'private');
        $response->setHeader('Pragma', 'private');
        $response->setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
        $response->setContent(file_get_contents($filePath));

        return $response;
    }
}