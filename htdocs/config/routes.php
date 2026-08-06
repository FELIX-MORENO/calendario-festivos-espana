<?php

/**
 * Archivo de definición de rutas del proyecto
 * 
 * Formato: 'URL' => ['Controller', 'method', 'middleware']
 */

$apiBase = $_ENV['API_BASE'];

return [
    // ============================================
    // 🔥 RUTAS DE LA API REST (DEBEN IR PRIMERO)
    // ============================================
    $apiBase.'/municipios' => ['ApiController', 'getMunicipios', 'ApiAuthMiddleware'],
    $apiBase.'/festivos-municipio' => ['ApiController', 'getFestivosMunicipio', 'ApiAuthMiddleware'],

    //$apiBase.'/festivos/municipio' => ['ApiController', 'getFestivosByMunicipio', 'ApiAuthMiddleware'],
    $apiBase.'/comunidades-autonomas' => ['ApiController', 'getComunidadesAutonomas', 'ApiAuthMiddleware'],
    $apiBase.'/festivos-comunidad-autonoma' => ['ApiController', 'getFestivosComunidadAutonoma', 'ApiAuthMiddleware'],

    $apiBase.'/festivos-nacionales' => ['ApiController', 'getFestivosNacionales', 'ApiAuthMiddleware'],

    $apiBase.'/municipios/buscar' => ['ApiController', 'buscarMunicipios', 'ApiAuthMiddleware'],
    $apiBase.'/admin/festivos' => ['ApiController', 'crearFestivo', 'ApiAuthMiddleware'],
    $apiBase.'/admin/festivos/editar' => ['ApiController', 'editarFestivo', 'ApiAuthMiddleware'],
    $apiBase.'/admin/festivos/eliminar' => ['ApiController', 'eliminarFestivo', 'ApiAuthMiddleware'],

    // ============================================
    // RUTAS PÚBLICAS (Sin autenticación)
    // ============================================
    '/' => ['PublicController', 'index', ''],
    'municipios' => ['PublicController', 'getMunicipios', ''],
    'comunidades-autonomas' => ['PublicController', 'getComunidadesAutonomas', ''],
    'nacionales' => ['PublicController', 'getNacionales', ''],

    'festivos' => ['PublicController', 'getFestivos', ''],
    
    // ============================================
    // RUTAS DE AUTENTICACIÓN
    // ============================================
    'admin/login' => ['AuthController', 'login', 'guest'],
    'admin/logout' => ['AuthController', 'logout', 'auth'],
    
    // ============================================
    // RUTAS DEL PANEL ADMIN
    // ============================================
    'admin' => ['AdminController', 'dashboard', 'auth'],
    'admin/municipios' => ['AdminController', 'municipios', 'auth'],
    'admin/municipios/crear' => ['AdminController', 'crearMunicipio', 'auth'],
    'admin/municipios/editar' => ['AdminController', 'editarMunicipio', 'auth'],
    'admin/municipios/eliminar' => ['AdminController', 'eliminarMunicipio', 'auth'],
    'admin/festivos' => ['AdminController', 'festivos', 'auth'],
    'admin/festivos/crear' => ['AdminController', 'crearFestivo', 'auth'],
    'admin/festivos/editar' => ['AdminController', 'editarFestivo', 'auth'],
    'admin/festivos/eliminar' => ['AdminController', 'eliminarFestivo', 'auth'],
    'admin/festivos/sincronizar' => ['SyncController', 'sincronizar', 'auth'],
    'admin/api-keys' => ['AdminController', 'apiKeys', 'auth'],
    'admin/api-keys/crear' => ['AdminController', 'crearApiKey', 'auth'],
    'admin/api-keys/revocar' => ['AdminController', 'revocarApiKey', 'auth'],
    'admin/logs' => ['AdminController', 'logs', 'auth'],
];