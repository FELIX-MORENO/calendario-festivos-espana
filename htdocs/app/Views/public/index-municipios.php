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
$js_adicional = ['assets/js/index-municipios.js'];

// Incluir el header
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- ============================================ -->
<!-- CONTENIDO PRINCIPAL DE LA VISTA -->
<!-- ============================================ -->

<div class="container py-1">
    
    <!-- ============================================ -->
    <!-- FORMULARIO DE BÚSQUEDA -->
    <!-- ============================================ -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    
                    <div class="row g-4 align-items-end">
                        
                        <!-- Selector de Municipio -->
                        <div class="col-12">
                            <h1 class="text-primary">Festivos Municipios</h1>
                            <label for="municipio" class="form-label fw-semibold">
                                <i class="fas fa-city text-primary"></i> Municipio
                            </label>
                            <select id="municipio" class="form-select form-select-lg" required size="1" >
                                <option value="">Selecciona un municipio...</option>
                            </select>
                            <div class="form-text" id="municipio-help">
                                <i class="fas fa-spinner fa-spin text-muted" id="municipio-loading"></i>
                                <span id="municipio-status">Cargando municipios...</span>
                            </div>
                        </div>

                        <!-- Selector de Año -->
                        <div class="col-12">
                            <label for="anio" class="form-label fw-semibold">
                                <i class="fas fa-calendar text-primary"></i> Año
                            </label>
                            <select id="anio" class="form-select form-select-lg">
                                <?php for ($i = date('Y'); $i >= 2024; $i--): ?>
                                    <option value="<?= $i ?>" <?= $i === ($anio_actual ?? date('Y')) ? 'selected' : '' ?>>
                                        <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Botón Consultar -->
                        <div class="col-12">
                            <button id="btnConsultar" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-search"></i> Consultar
                            </button>
                        </div>

                    </div>

                    <!-- Badge de estado -->
                    <div class="mt-3">
                        <span id="statusBadge" class="badge bg-secondary bg-opacity-10 text-secondary">
                            <i class="fas fa-circle-notch fa-spin"></i> Listo
                        </span>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- CONTENEDOR DE RESULTADOS -->
    <!-- ============================================ -->
    <div class="row justify-content-center mt-4">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-4" id="resultsCard">
                <div class="card-body p-4 p-md-5">

                    <!-- Placeholder (mensaje inicial) -->
                    <div id="placeholder" class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted opacity-25 mb-3"></i>
                        <h5 class="text-muted">Selecciona un municipio y año</h5>
                        <p class="text-muted small">
                            <i class="fas fa-arrow-up text-primary"></i> 
                            Elige un municipio del desplegable y pulsa "Consultar"
                        </p>
                    </div>

                    <!-- Contenido de resultados (oculto inicialmente) -->
                    <div id="resultsContent" style="display: none;"></div>

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