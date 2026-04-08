<?php

return [
    'url'      => env('MOODLE_URL', ''),
    'token'    => env('MOODLE_TOKEN', ''),
    'endpoint' => '/webservice/rest/server.php',

    'cache_ttl'                => env('MOODLE_CACHE_TTL', 1800),
    'cache_ttl_cursos'         => env('MOODLE_CACHE_CURSOS', 3600),
    'cache_ttl_calificaciones' => env('MOODLE_CACHE_CALIFICACIONES', 1800),
    'cache_ttl_accesos'        => env('MOODLE_CACHE_ACCESOS', 300),

    /*
    |--------------------------------------------------------------------------
    | Categorías de Educación a Distancia
    |--------------------------------------------------------------------------
    */
    'categorias_ead' => [450],
];