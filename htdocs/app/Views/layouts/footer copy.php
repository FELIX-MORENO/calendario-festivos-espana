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
    <!-- JAVASCRIPT PERSONALIZADO (BASE) -->
    <!-- ============================================ -->
    <script src="/assets/js/app.js"></script>

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
        window.CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?? '' ?>';
        
        console.log('🔑 API_KEY desde servidor:', window.API_KEY);
        console.log('🌐 API_BASE_URL:', window.API_BASE_URL);
        console.log('🌐 API_BASE:', window.API_BASE);
        console.log('🌐 DIRECCION API COMPLETA:', window.API_BASE_URL+'/'+window.API_BASE);

        // ============================================
        // 2. CONFIGURAR AXIOS
        // ============================================
        if (typeof axios !== 'undefined') {
            // ✅ Usar la API Key desde window.API_KEY
            axios.defaults.headers.common['X-API-Key'] = window.API_KEY;
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            axios.defaults.headers.common['X-CSRF-TOKEN'] = window.CSRF_TOKEN;
            console.log('✅ Axios configurado con API Key:', window.API_KEY);
        } else {
            console.error('❌ Axios no está disponible');
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