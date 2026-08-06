-- ============================================================
-- BASE DE DATOS: gestion_festivos
-- DESCRIPCIÓN: Creación de todas las tablas del sistema
-- FECHA: 2026-08-03
-- ============================================================

-- ============================================================
-- 1. TABLA: usuarios (Administradores del sistema)
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'editor') DEFAULT 'editor',
    ultimo_acceso TIMESTAMP NULL,
    
    -- Campos de auditoría
    activo TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    creado_por INT NULL COMMENT 'ID del usuario que creó',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modificado_por INT NULL COMMENT 'ID del usuario que modificó',
    modificado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_activo (activo),
    INDEX idx_rol (rol),
    INDEX idx_creado_por (creado_por),
    INDEX idx_modificado_por (modificado_por),
    
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. TABLA: comunidades_autonomas
-- ============================================================
CREATE TABLE IF NOT EXISTS comunidades_autonomas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL,
    codigo VARCHAR(10) UNIQUE COMMENT 'Código oficial (ej: AN, CT, etc.)',
    
    -- Campos de auditoría
    activo TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    creado_por INT NULL COMMENT 'ID del usuario que creó',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modificado_por INT NULL COMMENT 'ID del usuario que modificó',
    modificado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_activo (activo),
    INDEX idx_nombre (nombre),
    INDEX idx_creado_por (creado_por),
    INDEX idx_modificado_por (modificado_por),
    
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. TABLA: provincias
-- ============================================================
CREATE TABLE IF NOT EXISTS provincias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(10) UNIQUE COMMENT 'Código oficial (ej: 29, 18, etc.)',
    comunidad_id INT NOT NULL COMMENT 'ID de la comunidad autónoma',
    
    -- Campos de auditoría
    activo TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    creado_por INT NULL COMMENT 'ID del usuario que creó',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modificado_por INT NULL COMMENT 'ID del usuario que modificó',
    modificado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_activo (activo),
    INDEX idx_nombre (nombre),
    INDEX idx_comunidad_id (comunidad_id),
    INDEX idx_creado_por (creado_por),
    INDEX idx_modificado_por (modificado_por),
    
    FOREIGN KEY (comunidad_id) REFERENCES comunidades_autonomas(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. TABLA: municipios
-- ============================================================
CREATE TABLE IF NOT EXISTS municipios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_ine VARCHAR(5) UNIQUE NOT NULL COMMENT 'Código INE oficial (5 dígitos)',
    nombre VARCHAR(100) NOT NULL,
    provincia_id INT NOT NULL COMMENT 'ID de la provincia',
    
    -- Campos de auditoría
    activo TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    creado_por INT NULL COMMENT 'ID del usuario que creó',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modificado_por INT NULL COMMENT 'ID del usuario que modificó',
    modificado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_activo (activo),
    INDEX idx_nombre (nombre),
    INDEX idx_codigo_ine (codigo_ine),
    INDEX idx_provincia_id (provincia_id),
    INDEX idx_creado_por (creado_por),
    INDEX idx_modificado_por (modificado_por),
    
    FOREIGN KEY (provincia_id) REFERENCES provincias(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. TABLA: festivos
-- ============================================================
CREATE TABLE IF NOT EXISTS festivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    fecha DATE NOT NULL,
    anio INT NOT NULL COMMENT 'Año al que pertenece el festivo',
    tipo ENUM('Nacional', 'Autonómico', 'Provincial', 'Local') NOT NULL,
    
    -- Relaciones (solo una será NOT NULL según el tipo)
    comunidad_id INT NULL COMMENT 'FK a comunidades_autonomas (solo para tipo Autonómico)',
    provincia_id INT NULL COMMENT 'FK a provincias (solo para tipo Provincial)',
    municipio_id INT NULL COMMENT 'FK a municipios (solo para tipo Local)',
    
    -- Campos de auditoría
    activo TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    creado_por INT NULL COMMENT 'ID del usuario que creó',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modificado_por INT NULL COMMENT 'ID del usuario que modificó',
    modificado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_activo (activo),
    INDEX idx_fecha (fecha),
    INDEX idx_anio (anio),
    INDEX idx_tipo (tipo),
    INDEX idx_comunidad_id (comunidad_id),
    INDEX idx_provincia_id (provincia_id),
    INDEX idx_municipio_id (municipio_id),
    INDEX idx_creado_por (creado_por),
    INDEX idx_modificado_por (modificado_por),
    
    -- Clave única para evitar duplicados (mismo festivo en misma entidad y año)
    UNIQUE KEY unique_festivo (fecha, tipo, comunidad_id, provincia_id, municipio_id, anio),
    
    FOREIGN KEY (comunidad_id) REFERENCES comunidades_autonomas(id) ON DELETE CASCADE,
    FOREIGN KEY (provincia_id) REFERENCES provincias(id) ON DELETE CASCADE,
    FOREIGN KEY (municipio_id) REFERENCES municipios(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    
    -- Restricción para asegurar que solo una relación sea NOT NULL según el tipo
    CONSTRAINT check_tipo_relacion CHECK (
        (tipo = 'Nacional' AND comunidad_id IS NULL AND provincia_id IS NULL AND municipio_id IS NULL) OR
        (tipo = 'Autonómico' AND comunidad_id IS NOT NULL AND provincia_id IS NULL AND municipio_id IS NULL) OR
        (tipo = 'Provincial' AND comunidad_id IS NULL AND provincia_id IS NOT NULL AND municipio_id IS NULL) OR
        (tipo = 'Local' AND comunidad_id IS NULL AND provincia_id IS NULL AND municipio_id IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. TABLA: api_keys (Para consumir nuestra API REST)
-- ============================================================
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre descriptivo (ej: App Móvil, Web Socios)',
    api_key VARCHAR(64) UNIQUE NOT NULL COMMENT 'Clave generada (hash o UUID)',
    dominio_permitido VARCHAR(255) NOT NULL COMMENT 'Único dominio autorizado para esta clave',
    expira_en TIMESTAMP NULL COMMENT 'Fecha de expiración (NULL = sin expiración)',
    ultimo_uso TIMESTAMP NULL COMMENT 'Última fecha de uso',
    
    -- Campos de auditoría
    activo TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    creado_por INT NULL COMMENT 'ID del usuario que creó',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modificado_por INT NULL COMMENT 'ID del usuario que modificó',
    modificado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_activo (activo),
    INDEX idx_api_key (api_key),
    INDEX idx_dominio (dominio_permitido),
    INDEX idx_expira_en (expira_en),
    INDEX idx_creado_por (creado_por),
    INDEX idx_modificado_por (modificado_por),
    
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. TABLA: intentos_login (Control de intentos fallidos)
-- ============================================================
CREATE TABLE IF NOT EXISTS intentos_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL COMMENT 'Dirección IP (IPv4 o IPv6)',
    email VARCHAR(150) NULL COMMENT 'Email intentado (para auditoría)',
    intentos INT DEFAULT 1 COMMENT 'Número de intentos fallidos consecutivos',
    primer_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Primer intento fallido',
    ultimo_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Último intento fallido',
    bloqueado_hasta TIMESTAMP NULL COMMENT 'Fecha hasta la que está bloqueado',
    resuelto TINYINT(1) DEFAULT 0 COMMENT '0=Pendiente, 1=Resuelto (login exitoso)',
    
    INDEX idx_ip (ip),
    INDEX idx_bloqueado_hasta (bloqueado_hasta),
    INDEX idx_email (email),
    INDEX idx_resuelto (resuelto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. TABLA: api_requests (Rate limiting por API Key)
-- ============================================================
CREATE TABLE IF NOT EXISTS api_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT NOT NULL COMMENT 'ID de la API Key que realizó la petición',
    endpoint VARCHAR(255) NOT NULL COMMENT 'Endpoint consultado',
    ip VARCHAR(45) NOT NULL COMMENT 'IP del cliente',
    metodo VARCHAR(10) NOT NULL COMMENT 'Método HTTP (GET, POST, etc.)',
    status_code INT NOT NULL COMMENT 'Código de respuesta HTTP',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la petición',
    
    INDEX idx_api_key_id (api_key_id),
    INDEX idx_fecha (fecha),
    INDEX idx_ip (ip),
    
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. TABLA: logs (Opcional - para guardar eventos importantes)
-- ============================================================
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nivel VARCHAR(20) NOT NULL COMMENT 'DEBUG, INFO, WARNING, ERROR, CRITICAL',
    mensaje TEXT NOT NULL,
    contexto JSON NULL COMMENT 'Datos adicionales (usuario, IP, etc.)',
    archivo VARCHAR(255) NULL COMMENT 'Archivo donde ocurrió',
    linea INT NULL COMMENT 'Línea del archivo',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_nivel (nivel),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================