<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Permitindo qualquer origem (inclusive null/file://) — útil para testes locais
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Se não precisa enviar cookies ou headers com credenciais, melhor deixar false
    'supports_credentials' => false,

];
