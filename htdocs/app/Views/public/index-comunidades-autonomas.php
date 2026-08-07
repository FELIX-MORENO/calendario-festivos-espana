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
$js_adicional = ['assets/js/index-comunidades-autonomas.js'];

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
                            <h1 class="text-primary">Festivos Comunidades Autónomas</h1>
                            <label for="municipio" class="form-label fw-semibold">
                                <i class="fas fa-city text-primary"></i> Comunidad Autónoma
                            </label>
                            <select id="municipio" class="form-select form-select-lg" required size="1" >
                                <option value="">Selecciona una Comunidad Autónoma...</option>
                            </select>
                            <div class="form-text" id="municipio-help">
                                <i class="fas fa-spinner fa-spin text-muted" id="municipio-loading"></i>
                                <span id="municipio-status">Cargando Comunidades Autónomas...</span>
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
                        <h5 class="text-muted">Selecciona una provincia y año</h5>
                        <p class="text-muted small">
                            <i class="fas fa-arrow-up text-primary"></i> 
                            Elige una provincia del desplegable y pulsa "Consultar"
                        </p>
                    </div>

                    <!-- Contenido de resultados (oculto inicialmente) -->
                    <div id="resultsContent" style="display: none;"></div>

                </div>
            </div>
        </div>
    </div>

</div>


<?php
// Incluir el footer
require_once __DIR__ . '/../layouts/footer.php';
?>