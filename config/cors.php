<?php

return [
  'paths' => ['api/*', 'sanctum/csrf-cookie', 'digital/stream/*'],
  'allowed_methods' => ['*'],
  'allowed_origins' => array_filter([
    env('FRONTEND_URL', 'http://localhost:3001'),
    'http://localhost:3000',
    'http://localhost:3001',
    'http://localhost:3002',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:3001',
    'http://127.0.0.1:3002',
    'https://kenluamba.com',
    'https://www.kenluamba.com',
    'https://admin.kenluamba.com',
  ]),
  'allowed_origins_patterns' => [
    '#^https?://localhost(:\d+)?$#',
    '#^https?://127\.0\.0\.1(:\d+)?$#',
    '#^https?://([a-z0-9-]+\.)?kenluamba\.com$#',
  ],
  'allowed_headers' => ['*'],
  'exposed_headers' => ['Content-Disposition', 'Content-Type', 'Content-Length'],
  'max_age' => 0,
  'supports_credentials' => true,
];
