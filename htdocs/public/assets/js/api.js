/**
 * Módulo de comunicación con la API REST
 * Usa Axios para peticiones HTTP
 */

const API = {
    /**
     * Obtener festivos de un municipio y año
     */
    getFestivos(municipioId, anio) {
        return axios.get(`/api/v1/festivos/municipio`, {
            params: {
                municipio_id: municipioId,
                anio: anio
            }
        });
    },

    /**
     * Obtener lista de municipios
     */
    getMunicipios(term) {
        return axios.get(`/api/v1/municipios/buscar`, {
            params: {
                term: term || ''
            }
        });
    },

    /**
     * Sincronizar festivos desde API externa (solo admin)
     */
    syncFestivos(anio) {
        return axios.post(`/admin/festivos/sincronizar`, {
            anio: anio
        });
    }
};

// Exportar para usar en otros módulos
window.API = API;