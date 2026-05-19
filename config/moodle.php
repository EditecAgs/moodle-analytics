<?php

$env = env('MOODLE_ENV', 'production');
$isTest = $env === 'test';

return [
    'url'      => $isTest ? env('MOODLE_TEST_URL', '') : env('MOODLE_URL', ''),
    'token'    => $isTest ? env('MOODLE_TEST_TOKEN', '') : env('MOODLE_TOKEN', ''),
    'endpoint' => '/webservice/rest/server.php',

    
    'verify_ssl' => env('MOODLE_VERIFY_SSL', false),

    'cache_ttl'                => env('MOODLE_CACHE_TTL', 1800),
    'cache_ttl_cursos'         => env('MOODLE_CACHE_CURSOS', 3600),
    'cache_ttl_calificaciones' => env('MOODLE_CACHE_CALIFICACIONES', 1800),
    'cache_ttl_accesos'        => env('MOODLE_CACHE_ACCESOS', 300),

    'categorias_ead' => $isTest
        ? array_map('intval', explode(',', env('MOODLE_TEST_CATEGORIES', '1')))
        : array_map('intval', explode(',', env('MOODLE_CATEGORIES', '450'))),
];