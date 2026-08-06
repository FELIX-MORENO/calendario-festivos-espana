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
        <div class="col-lg-8 text-center">
            <p class="lead text-muted">
                <?= $descripcion ?? 'Consulta los días festivos de cualquier municipio de España de forma rápida y sencilla.' ?>
            </p>
            <hr class="w-50 mx-auto">
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

<!-- ============================================ -->
<!-- ESTILOS ADICIONALES (solo para esta vista) -->
<!-- ============================================ -->
<style>
    .festivo-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 16px;
        margin-bottom: 6px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #0d6efd;
        transition: all 0.2s ease;
    }
    .festivo-item:hover {
        background: #e9ecef;
        transform: translateX(4px);
    }
    .festivo-fecha {
        font-weight: 600;
        color: #0d6efd;
        min-width: 110px;
        font-size: 0.9rem;
    }
    .festivo-nombre {
        flex: 1;
        margin: 0 15px;
        font-size: 0.95rem;
    }
    .festivo-nivel {
        font-size: 0.7rem;
        padding: 4px 14px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        min-width: 90px;
        text-align: center;
    }
    .nivel-Nacional { background: #cfe2ff; color: #0a58ca; }
    .nivel-Autonómico { background: #d1e7dd; color: #0f5132; }
    .nivel-Provincial { background: #fff3cd; color: #856404; }
    .nivel-Local { background: #f8d7da; color: #842029; }
    
    @media (max-width: 576px) {
        .festivo-item {
            flex-wrap: wrap;
        }
        .festivo-fecha {
            width: 100%;
            margin-bottom: 4px;
        }
        .festivo-nombre {
            width: 100%;
            margin: 4px 0;
        }
        .festivo-nivel {
            width: 100%;
            text-align: left;
        }
    }
</style>

<?php
// Incluir el footer
require_once __DIR__ . '/../layouts/footer.php';
?>