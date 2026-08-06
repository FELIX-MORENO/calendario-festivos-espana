<?php
/**
 * Punto de entrada para producción (InfinityFree)
 * 
 * Este archivo incluye public/index.php manteniendo el mismo contexto
 */
//header('Location: public/index.php'); //-> lo redirigia pero voy a hacer que incluya el index
// Incluir el index.php de la carpeta public
require_once __DIR__ . '/public/index.php';
exit;