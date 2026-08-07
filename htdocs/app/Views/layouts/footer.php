    <!-- ============================================ -->
    <!-- BOOTSTRAP 5 (JavaScript Bundle con Popper) -->
    <!-- ============================================ -->
    <script 
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" 
        crossorigin="anonymous">
    </script>

    <!-- ============================================ -->
    <!-- AXIOS (Cliente HTTP para AJAX) -->
    <!-- ============================================ -->
    <script src="https://cdn.jsdelivr.net/npm/axios@1.19.0/dist/axios.min.js"></script>

    <!-- ============================================ -->
    <!-- SELECT2 (select con buscador) -->
    <!-- ============================================ -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>    
    <!-- ============================================ -->
    <!-- JAVASCRIPT PERSONALIZADO (BASE) -->
    <!-- ============================================ -->
    <script src="assets/js/app.js"></script>

    <!-- ============================================ -->
    <!-- ✅ CONFIGURACIÓN GLOBAL (ÚNICA FUENTE) -->
    <!-- ============================================ -->
    <script>
        // ============================================
        // 1. VARIABLES GLOBALES (desde el servidor)
        // ============================================
        window.API_KEY = '<?= $_ENV['API_KEY'] ?>';
        window.API_BASE_URL = '<?= $_ENV['APP_URL'] ?>';
        window.API_BASE = '<?= $_ENV['API_BASE'] ?>';
        window.BASE_URL = '<?= $_ENV['BASE_URL'] ?>';
        window.CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?? '' ?>';

        // ============================================
        // 2. CONFIGURAR AXIOS
        // ============================================
        // Configurar Axios con la BASE_URL
        if (typeof axios !== 'undefined' && typeof window.APP_CONFIG !== 'undefined') {
            const config = window.APP_CONFIG;
            
            // ✅ URL base para Axios
            const BASE_URL = config.BASE_URL || '';
            
            // Configurar Axios
            axios.defaults.baseURL = BASE_URL ? '/' + BASE_URL : '/';
            axios.defaults.headers.common['X-API-Key'] = config.API_KEY;
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            axios.defaults.headers.common['X-CSRF-TOKEN'] = config.CSRF_TOKEN;
        } else {
            console.error('❌ Axios o APP_CONFIG no está disponible');
        }
    </script>

    <!-- ============================================ -->
    <!-- SCRIPTS ESPECÍFICOS POR PÁGINA (se cargan DESPUÉS de la configuración) -->
    <!-- ============================================ -->
    <?php if (isset($js_adicional)): ?>
        <?php foreach ($js_adicional as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>