<?php

namespace CRUDAPIGenerator\API;

use CRUDAPIGenerator\Database\Database;

class Documentation
{
    private Database $database;
    private array $tables;
    private array $endpoints = [];

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->tables = $database->getTables();
        $this->generateEndpoints();
    }

    private function generateEndpoints(): void
    {
        foreach ($this->tables as $table) {
            $structure = $this->database->getTableStructure($table);
            $primaryKey = $this->database->getPrimaryKey($table);

            $this->endpoints[$table] = [
                'list' => [
                    'method' => 'GET',
                    'path' => "/api/{$table}",
                    'description' => "List all {$table} records",
                    'parameters' => [
                        'query' => [
                            'page' => ['type' => 'integer', 'description' => 'Page number'],
                            'limit' => ['type' => 'integer', 'description' => 'Number of records per page'],
                            'sort' => ['type' => 'string', 'description' => 'Sort field'],
                            'order' => ['type' => 'string', 'description' => 'Sort order (asc/desc)']
                        ]
                    ],
                    'response' => [
                        'status' => 'success',
                        'data' => [
                            'items' => ['type' => 'array', 'description' => 'List of records'],
                            'total' => ['type' => 'integer', 'description' => 'Total number of records'],
                            'page' => ['type' => 'integer', 'description' => 'Current page'],
                            'limit' => ['type' => 'integer', 'description' => 'Records per page']
                        ]
                    ]
                ],
                'get' => [
                    'method' => 'GET',
                    'path' => "/api/{$table}/{id}",
                    'description' => "Get a single {$table} record",
                    'parameters' => [
                        'path' => [
                            'id' => ['type' => 'integer', 'description' => 'Record ID']
                        ]
                    ],
                    'response' => [
                        'status' => 'success',
                        'data' => ['type' => 'object', 'description' => 'Record data']
                    ]
                ],
                'create' => [
                    'method' => 'POST',
                    'path' => "/api/{$table}",
                    'description' => "Create a new {$table} record",
                    'parameters' => [
                        'body' => $this->generateBodyParameters($structure)
                    ],
                    'response' => [
                        'status' => 'success',
                        'message' => ['type' => 'string', 'description' => 'Success message'],
                        'data' => [
                            'id' => ['type' => 'integer', 'description' => 'Created record ID']
                        ]
                    ]
                ],
                'update' => [
                    'method' => 'PUT',
                    'path' => "/api/{$table}/{id}",
                    'description' => "Update a {$table} record",
                    'parameters' => [
                        'path' => [
                            'id' => ['type' => 'integer', 'description' => 'Record ID']
                        ],
                        'body' => $this->generateBodyParameters($structure, true)
                    ],
                    'response' => [
                        'status' => 'success',
                        'message' => ['type' => 'string', 'description' => 'Success message']
                    ]
                ],
                'delete' => [
                    'method' => 'DELETE',
                    'path' => "/api/{$table}/{id}",
                    'description' => "Delete a {$table} record",
                    'parameters' => [
                        'path' => [
                            'id' => ['type' => 'integer', 'description' => 'Record ID']
                        ]
                    ],
                    'response' => [
                        'status' => 'success',
                        'message' => ['type' => 'string', 'description' => 'Success message']
                    ]
                ]
            ];
        }
    }

    private function generateBodyParameters(array $structure, bool $isUpdate = false): array
    {
        $parameters = [];
        foreach ($structure as $field => $info) {
            if ($isUpdate && $field === $this->database->getPrimaryKey($info['table'])) {
                continue;
            }

            $parameters[$field] = [
                'type' => $this->getParameterType($info['type']),
                'description' => $this->getFieldDescription($field, $info),
                'required' => !$info['nullable']
            ];
        }
        return $parameters;
    }

    private function getParameterType(string $type): string
    {
        switch ($type) {
            case 'int':
            case 'bigint':
            case 'tinyint':
                return 'integer';
            case 'decimal':
            case 'float':
            case 'double':
                return 'number';
            case 'datetime':
            case 'timestamp':
                return 'string (datetime)';
            case 'date':
                return 'string (date)';
            case 'time':
                return 'string (time)';
            case 'boolean':
                return 'boolean';
            default:
                return 'string';
        }
    }

    private function getFieldDescription(string $field, array $info): string
    {
        $description = ucfirst(str_replace('_', ' ', $field));
        
        if ($info['nullable']) {
            $description .= ' (optional)';
        }

        if (isset($info['default'])) {
            $description .= ", default: {$info['default']}";
        }

        return $description;
    }

    public function generateMarkdown(): string
    {
        $markdown = "# API Documentation\n\n";
        $markdown .= "## Authentication\n\n";
        $markdown .= "All API requests require an API key to be included in the `X-API-Key` header.\n\n";
        $markdown .= "## Rate Limiting\n\n";
        $markdown .= "API requests are limited to 60 requests per minute per IP address.\n\n";
        $markdown .= "## Endpoints\n\n";

        foreach ($this->endpoints as $table => $endpoints) {
            $markdown .= "### {$table}\n\n";

            foreach ($endpoints as $name => $endpoint) {
                $markdown .= "#### {$endpoint['description']}\n\n";
                $markdown .= "**Method:** `{$endpoint['method']}`\n\n";
                $markdown .= "**Path:** `{$endpoint['path']}`\n\n";

                if (!empty($endpoint['parameters'])) {
                    $markdown .= "**Parameters:**\n\n";

                    foreach ($endpoint['parameters'] as $type => $parameters) {
                        $markdown .= "_{$type}_\n\n";
                        $markdown .= "| Name | Type | Required | Description |\n";
                        $markdown .= "|------|------|----------|-------------|\n";

                        foreach ($parameters as $name => $info) {
                            $required = isset($info['required']) && $info['required'] ? 'Yes' : 'No';
                            $markdown .= "| {$name} | {$info['type']} | {$required} | {$info['description']} |\n";
                        }

                        $markdown .= "\n";
                    }
                }

                $markdown .= "**Response:**\n\n";
                $markdown .= "```json\n";
                $markdown .= json_encode($endpoint['response'], JSON_PRETTY_PRINT);
                $markdown .= "\n```\n\n";
            }
        }

        return $markdown;
    }

    public function generateSwagger(): array
    {
        $swagger = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'CRUD API Generator',
                'version' => '1.0.0',
                'description' => 'Automatically generated CRUD API'
            ],
            'servers' => [
                [
                    'url' => '/api',
                    'description' => 'API Server'
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key'
                    ]
                ]
            ],
            'security' => [
                ['ApiKeyAuth' => []]
            ],
            'paths' => []
        ];

        foreach ($this->endpoints as $table => $endpoints) {
            foreach ($endpoints as $name => $endpoint) {
                $path = str_replace('/api/', '', $endpoint['path']);
                $method = strtolower($endpoint['method']);

                $swagger['paths'][$path][$method] = [
                    'summary' => $endpoint['description'],
                    'parameters' => $this->generateSwaggerParameters($endpoint['parameters']),
                    'responses' => [
                        '200' => [
                            'description' => 'Successful operation',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => $this->generateSwaggerResponse($endpoint['response'])
                                    ]
                                ]
                            ]
                        ],
                        '400' => [
                            'description' => 'Bad request'
                        ],
                        '401' => [
                            'description' => 'Unauthorized'
                        ],
                        '404' => [
                            'description' => 'Not found'
                        ],
                        '429' => [
                            'description' => 'Too many requests'
                        ]
                    ]
                ];
            }
        }

        return $swagger;
    }

    private function generateSwaggerParameters(array $parameters): array
    {
        $swaggerParameters = [];

        foreach ($parameters as $type => $params) {
            foreach ($params as $name => $info) {
                $parameter = [
                    'name' => $name,
                    'in' => $type,
                    'description' => $info['description'],
                    'required' => $info['required'] ?? false,
                    'schema' => [
                        'type' => $info['type']
                    ]
                ];

                $swaggerParameters[] = $parameter;
            }
        }

        return $swaggerParameters;
    }

    private function generateSwaggerResponse(array $response): array
    {
        $properties = [];

        foreach ($response as $key => $info) {
            $properties[$key] = [
                'type' => $info['type'],
                'description' => $info['description']
            ];
        }

        return $properties;
    }
} 