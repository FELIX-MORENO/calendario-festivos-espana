<?php
/**
 * Vista pública principal
 * 
 * Muestra el selector de municipio y año para consultar festivos.
 * 
 * Variables disponibles desde el controlador:
 *   $titulo       - Título de la página
 *   $descripcion  - Descripción para SEO
 *   $anio_actual  - Año actual (para el selector)
 * 
 * CSS/JS adicionales:
 *   $css_adicional - Array de archivos CSS específicos
 *   $js_adicional  - Array de archivos JS específicos
 */

// Configuración de CSS y JS adicionales
$css_adicional = [];
//$js_adicional = ['/assets/js/public.js'];

// Incluir el header
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- ============================================ -->
<!-- CONTENIDO PRINCIPAL DE LA VISTA -->
<!-- ============================================ -->

<div class="container py-5">

    <div class="row justify-content-center mb-5">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="row g-4">
                        <div class="col-12 text-center">
                            <p class="lead text-primary">
                                <?= $descripcion ?? 'Consulta los días festivos de cualquier municipio de España de forma rápida y sencilla.' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- FORMULARIO DE BÚSQUEDA -->
    <!-- ============================================ -->
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    
                    <div class="row g-4 align-items-end">
                        
                        <div class="col-md-4 text-center">
                                <i class="fas fa-city text-primary"></i> Municipio
                                <button id="btnConsultarMunicipio" class="btn btn-sm btn-primary w-100 mt-2"
                                 onclick="window.location.href='<?= $_ENV['BASE_URL'] ?? '' ?>/municipios'">
                                    <i class="fas fa-search"></i> Consultar
                                </button>
                        </div>

                        <div class="col-md-4 text-center">
                                <i class="fas fa-city text-info"></i> Comunidad Autónoma
                                <button id="btnConsultarComunidadAutonima" class="btn btn-sm btn-primary btn-lg w-100 mt-2"
                                 onclick="window.location.href='<?= $_ENV['BASE_URL'] ?? '' ?>/comunidades-autonomas'">
                                    <i class="fas fa-search"></i> Consultar
                                </button>
                        </div>

                        <div class="col-md-4 text-center">
                                <i class="fas fa-city text-success"></i> Nacionales
                                <button id="btnConsultarNacionales" class="btn btn-sm btn-primary btn-lg w-100 mt-2"
                                 onclick="window.location.href='<?= $_ENV['BASE_URL'] ?? '' ?>/nacionales'">
                                    <i class="fas fa-search"></i> Consultar
                                </button>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>


</div>


<?php
// Incluir el footer
require_once __DIR__ . '/../layouts/footer.php';
?>