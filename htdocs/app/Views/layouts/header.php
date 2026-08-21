<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Gestión de Festivos' ?></title>
    
    <!-- ============================================ -->
    <!-- ✅ BASE URL PARA TODAS LAS RUTAS RELATIVAS -->
    <!-- ============================================ -->
    <base href="<?= $_ENV['APP_URL'] ?? '' ?><?= $_ENV['BASE_URL'] ?? '' ?>/">

    <!-- ============================================ -->
    <!-- METAS Y SEO -->
    <!-- ============================================ -->
    <meta name="description" content="<?= $descripcion ?? 'Consulta los días festivos de cualquier municipio de España.' ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $_ENV['APP_URL'] ?? '' ?>">

    <!-- ============================================ -->
    <!-- FAVICON -->
    <!-- ============================================ -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">

    <!-- ============================================ -->
    <!-- ✅ CONFIGURACIÓN GLOBAL (SE CARGA PRIMERO) -->
    <!-- ============================================ -->
    <script src="assets/js/config.php"></script>

    <!-- ============================================ -->
    <!-- BOOTSTRAP 5 (CSS) -->
    <!-- ============================================ -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ============================================ -->
    <!-- FONT AWESOME 6 (Iconos) -->
    <!-- ============================================ -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


    <!-- ============================================ -->
    <!-- JQUERY (debe cargarse ANTES que Select2) -->
    <!-- ============================================ -->
    <script 
        src="https://code.jquery.com/jquery-3.7.1.min.js" 
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" 
        crossorigin="anonymous">
    </script>

    <!-- ============================================ -->
    <!-- SELECT 2                                      -->
    <!-- ============================================ -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js">
    </script>
    <!-- ============================================ -->
    <!-- SELECT2 TEMA BOOTSTRAP 5 (Estilo Bootstrap) -->
    <!-- ============================================ -->
    <link 
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" 
        rel="stylesheet" 
    />

    <!-- ============================================ -->
    <!-- ESTILOS PERSONALIZADOS -->
    <!-- ============================================ -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- ============================================ -->
    <!-- ESTILOS ESPECÍFICOS POR PÁGINA -->
    <!-- ============================================ -->
    <?php if (isset($css_adicional)): ?>
        <?php foreach ($css_adicional as $css): ?>
            <link rel="stylesheet" href="<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>

</head>
<body>
<?php require_once('header_html.php'); ?>