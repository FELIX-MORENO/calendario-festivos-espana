/**
 * Módulo de administración
 * Maneja las acciones del panel admin con AJAX
 */

document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // Sincronizar festivos
    // ============================================
    const syncForm = document.getElementById('sync-form');
    if (syncForm) {
        syncForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const anio = document.getElementById('sync-anio').value;
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sincronizando...';
            
            API.syncFestivos(anio)
                .then(response => {
                    if (response.data.success) {
                        showAlert('✅ Festivos sincronizados correctamente', 'success');
                        // Recargar la tabla de festivos
                        loadFestivos(anio);
                    } else {
                        showAlert('❌ Error: ' + response.data.message, 'danger');
                    }
                })
                .catch(error => {
                    showAlert('❌ Error en la petición: ' + error.message, 'danger');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        });
    }
});

/**
 * Función para mostrar alertas
 */
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alerts-container');
    if (!alertContainer) return;
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.role = 'alert';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}