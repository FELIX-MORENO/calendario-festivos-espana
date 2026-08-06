/**
 * Módulo de consulta de festivos para la vista pública
 * 
 * Dependencias: Axios (debe estar cargado antes)
 * 
 * Características:
 *   - Carga de municipios desde la API
 *   - IDs de municipios encriptados (seguridad)
 *   - Consulta de festivos por municipio y año
 *   - Renderizado estructurado por niveles (Nacional, Autonómico, Provincial, Local)
 *   - Feedback visual de carga y errores
 */

(function() {
    'use strict';

    // ============================================
    // ELEMENTOS DEL DOM
    // ============================================
    const municipioSelect = document.getElementById('municipio');
    const anioSelect = document.getElementById('anio');
    const btnConsultar = document.getElementById('btnConsultar');
    const placeholder = document.getElementById('placeholder');
    const resultsContent = document.getElementById('resultsContent');
    const statusBadge = document.getElementById('statusBadge');
    const municipioStatus = document.getElementById('municipio-status');
    const municipioLoading = document.getElementById('municipio-loading');

    // ============================================
    // CONFIGURACIÓN DE LA API (desde variables globales)
    // ============================================
    
    console.log('🔑 public.js usando API_KEY:', API_KEY);
    console.log('🌐 public.js usando API_BASE:', API_BASE);

    // ============================================
    // ESTADO DE LA APLICACIÓN
    // ============================================
    let isLoading = false;
    let municipiosCargados = false;

    // ============================================
    // FUNCIÓN: ACTUALIZAR ESTADO (badge)
    // ============================================
    function setStatus(message, type = 'info') {
        if (!statusBadge) return;

        const colors = {
            info: 'bg-secondary bg-opacity-10 text-secondary',
            success: 'bg-success bg-opacity-10 text-success',
            warning: 'bg-warning bg-opacity-10 text-warning',
            error: 'bg-danger bg-opacity-10 text-danger',
            loading: 'bg-primary bg-opacity-10 text-primary'
        };
        
        const icons = {
            info: '<i class="fas fa-info-circle"></i> ',
            success: '<i class="fas fa-check-circle"></i> ',
            warning: '<i class="fas fa-exclamation-triangle"></i> ',
            error: '<i class="fas fa-times-circle"></i> ',
            loading: '<i class="fas fa-circle-notch fa-spin"></i> '
        };

        statusBadge.className = 'badge ' + (colors[type] || colors.info);
        statusBadge.innerHTML = (icons[type] || '') + message;
    }

    // ============================================
    // FUNCIÓN: CARGAR MUNICIPIOS
    // ============================================
    async function loadMunicipios() {
        // Evitar cargar varias veces
        if (municipiosCargados) return;

        // Actualizar UI de carga
        if (municipioStatus) {
            municipioStatus.textContent = 'Cargando municipios...';
        }
        if (municipioLoading) {
            municipioLoading.style.display = 'inline-block';
        }
        setStatus('Cargando municipios desde la API...', 'loading');

        try {
            // ✅ Petición a la API con API Key
            let Direccion_API=API_BASE_URL+BASE_URL+'/'+API_BASE+'/municipios';
            console.log('La llamada a la API: '+Direccion_API);
            const response = await axios.get(Direccion_API, {
                headers: {
                    'X-API-Key': API_KEY
                }
            });
            
            if (response.data.success && response.data.data) {
                const municipios = response.data.data;
                
                // Limpiar select
                municipioSelect.innerHTML = '<option value="">Selecciona un municipio...</option>';
                
                // ✅ Añadir opciones con ID encriptado (ya viene encriptado del backend)
                if (municipios.length === 0) {
                    municipioSelect.innerHTML = '<option value="">No hay municipios disponibles</option>';
                    if (municipioStatus) {
                        municipioStatus.textContent = '⚠️ No hay municipios disponibles';
                    }
                    setStatus('No hay municipios disponibles', 'warning');
                    municipiosCargados = true;
                    return;
                }

                municipios.forEach(m => {
                    const option = document.createElement('option');
                    option.value = m.id;  // 🔒 ID encriptado recibido del backend
                    option.textContent = m.nombre;
                    // Guardar el ID encriptado como atributo data por si acaso
                    option.dataset.encryptedId = m.id;
                    municipioSelect.appendChild(option);
                });
                
                municipiosCargados = true;
                if (municipioStatus) {
                    municipioStatus.textContent = `✅ ${municipios.length} municipios cargados`;
                }
                setStatus(`${municipios.length} municipios disponibles`, 'success');
                
                // ✅ Seleccionar automáticamente el primer municipio (opcional)
                // Si quieres que cargue automáticamente al cargar la página:
                // if (municipios.length > 0) {
                //     municipioSelect.selectedIndex = 1;
                //     // consultarFestivos();
                // }
            } else {
                throw new Error(response.data.error || 'Error al cargar municipios');
            }

        } catch (error) {
            console.error('Error cargando municipios:', error);
            
            let errorMsg = 'Error al cargar municipios';
            if (error.response && error.response.data && error.response.data.error) {
                errorMsg = error.response.data.error;
            } else if (error.message) {
                errorMsg = error.message;
            }
            
            if (municipioStatus) {
                municipioStatus.textContent = `❌ ${errorMsg}`;
            }
            setStatus(`Error: ${errorMsg}`, 'error');
            
            municipioSelect.innerHTML = `<option value="">Error al cargar municipios</option>`;
        }

        if (municipioLoading) {
            municipioLoading.style.display = 'none';
        }
    }

    // ============================================
    // FUNCIÓN: CONSULTAR FESTIVOS
    // ============================================
    async function consultarFestivos() {
        // Evitar múltiples peticiones simultáneas
        if (isLoading) return;

        const encryptedMunicipioId = municipioSelect.value;  // 🔒 ID encriptado
        const anio = anioSelect.value;

        // Validar
        if (!encryptedMunicipioId) {
            setStatus('⚠️ Selecciona un municipio', 'warning');
            municipioSelect.focus();
            return;
        }

        // Estado de carga
        isLoading = true;
        btnConsultar.disabled = true;
        btnConsultar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
        setStatus(`Consultando festivos para ${anio}...`, 'loading');

        // Mostrar placeholder de carga
        placeholder.style.display = 'block';
        placeholder.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <h6 class="mt-3 text-muted">Consultando festivos...</h6>
                <p class="text-muted small">Esto puede tomar unos segundos</p>
            </div>
        `;
        resultsContent.style.display = 'none';

        try {
            // ✅ Enviar ID encriptado tal cual (ya viene encriptado del backend)
            let Direccion_API=API_BASE_URL+BASE_URL+'/'+API_BASE+'/festivos-municipio';
            console.log('La llamada a la API: '+Direccion_API);            
            const response = await axios.get(Direccion_API, {
                params: {
                    municipio_id: encryptedMunicipioId,  // 🔒 ID encriptado
                    anio: anio
                },
                headers: {
                    'X-API-Key': API_KEY
                }
            });

            if (response.data.success) {
                renderFestivos(response.data);
                setStatus(`✅ ${response.data.total || 0} festivos encontrados`, 'success');
            } else {
                showError(response.data.error || 'Error al obtener los festivos.');
                setStatus('❌ Error en la consulta', 'error');
            }

        } catch (error) {
            console.error('Error en la consulta:', error);
            
            let msg = 'Error al conectar con el servidor.';
            if (error.response && error.response.data && error.response.data.error) {
                msg = error.response.data.error;
            } else if (error.message) {
                msg = error.message;
            }
            showError(msg);
            setStatus('❌ Error: ' + msg, 'error');
        }

        // Restaurar botón
        isLoading = false;
        btnConsultar.disabled = false;
        btnConsultar.innerHTML = '<i class="fas fa-search"></i> Consultar';
    }

    // ============================================
    // FUNCIÓN: RENDERIZAR FESTIVOS
    // ============================================
    function renderFestivos(response) {
        const data = response.data || [];
        const total = response.total || 0;
        const encryptedMunicipioId = response.municipio_id || '';
        const anio = response.anio || '';

        // Ocultar placeholder
        placeholder.style.display = 'none';
        resultsContent.style.display = 'block';

        if (!data || data.length === 0) {
            resultsContent.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-muted opacity-25 mb-3"></i>
                    <h5 class="text-muted">No se encontraron festivos</h5>
                    <p class="text-muted small">Para el municipio y año seleccionados no hay festivos registrados.</p>
                </div>
            `;
            return;
        }

        // Configuración de niveles
        const niveles = {
            'Nacional': { label: '🏛️ Nacional', class: 'nivel-Nacional' },
            'Autonómico': { label: '🏢 Autonómico', class: 'nivel-Autonómico' },
            'Provincial': { label: '📍 Provincial', class: 'nivel-Provincial' },
            'Local': { label: '🏘️ Local', class: 'nivel-Local' }
        };

        // Agrupar por nivel
        const grouped = {};
        data.forEach(f => {
            const nivel = f.nivel || f.tipo || 'Otro';
            if (!grouped[nivel]) grouped[nivel] = [];
            grouped[nivel].push(f);
        });

        // Construir HTML
        let html = `
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h5 class="mb-0">
                    <i class="fas fa-flag text-primary"></i> Festivos
                    <span class="text-muted" style="font-size: 0.85rem; font-weight: normal;">
                        (ID: ${encryptedMunicipioId.substring(0, 10)}... · ${anio})
                    </span>
                </h5>
                <span class="badge bg-primary rounded-pill px-3 py-2">
                    <i class="fas fa-calendar-day"></i> ${total} festivos
                </span>
            </div>
        `;

        // Mostrar por nivel
        for (const [nivel, festivos] of Object.entries(grouped)) {
            const nivelInfo = niveles[nivel] || { label: nivel, class: '' };
            
            html += `
                <div class="mb-3">
                    <small class="text-muted fw-semibold">
                        ${nivelInfo.label} (${festivos.length})
                    </small>
            `;
            
            festivos.forEach((f, index) => {
                // Alternar colores para mejor legibilidad
                const bgColor = index % 2 === 0 ? '#f8f9fa' : '#ffffff';
                
                html += `
                    <div class="festivo-item" style="background-color: ${bgColor};">
                        <span class="festivo-fecha">
                            <i class="far fa-calendar-alt"></i> ${f.fecha}
                        </span>
                        <span class="festivo-nombre">
                            <strong>${f.nombre}</strong>
                        </span>
                        <span class="festivo-nivel ${nivelInfo.class}">
                            ${nivel}
                        </span>
                    </div>
                `;
            });
            
            html += `</div>`;
        }

        resultsContent.innerHTML = html;
    }

    // ============================================
    // FUNCIÓN: MOSTRAR ERROR
    // ============================================
    function showError(message) {
        placeholder.style.display = 'none';
        resultsContent.style.display = 'block';
        resultsContent.innerHTML = `
            <div class="alert alert-danger border-0 rounded-4 d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem;"></i>
                <div>
                    <strong>Error:</strong> ${message}
                </div>
            </div>
        `;
    }

    // ============================================
    // INICIALIZACIÓN
    // ============================================
    async function init() {
        // Verificar que Axios está cargado
        if (typeof axios === 'undefined') {
            console.error('❌ Axios no está cargado. Reintentando en 500ms...');
            setTimeout(init, 500);
            return;
        }

        console.log('✅ Inicializando módulo público...');

        // Configurar Axios globalmente (por si acaso)
        try {
            axios.defaults.headers.common['X-API-Key'] = API_KEY;
            // NO establecer 'Origin' manualmente (el navegador lo envía automáticamente)
        } catch (e) {
            console.warn('No se pudo configurar Axios globalmente:', e);
        }

        // Cargar municipios
        await loadMunicipios();
        
        // Evento del botón consultar
        btnConsultar.addEventListener('click', consultarFestivos);
        
        // Enter en los selects
        municipioSelect.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                consultarFestivos();
            }
        });
        anioSelect.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                consultarFestivos();
            }
        });

        // Cambio en el selector de municipio (opcional: consulta automática)
        // municipioSelect.addEventListener('change', () => {
        //     if (municipioSelect.value) {
        //         consultarFestivos();
        //     }
        // });

        console.log('✅ Módulo público inicializado correctamente');
    }

    // ============================================
    // EJECUCIÓN
    // ============================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();