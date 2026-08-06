<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Debugable;
use PDO;
use PDOStatement;
use PDOException;
use Exception;

/**
 * Servicio de sincronización con la API externa
 * 
 * Maneja la importación de comunidades, provincias, municipios y festivos
 * desde calendariosnacionales.com
 * 
 * Estructura del JSON:
 *   - spain.holidays: Festivos nacionales
 *   - regions[]: Comunidades autónomas
 *       - code: Código (ej: "AND")
 *       - name: Nombre
 *       - holidays[]: Festivos autonómicos
 *       - provinces[]: Provincias de la comunidad
 *           - code: Código (ej: "04")
 *           - name: Nombre
 *           - holidays[]: Festivos provinciales
 *           - municipalities[]: Municipios de la provincia
 *               - ine: Código INE (ej: "04001")
 *               - name: Nombre
 *               - holidays[]: Festivos locales
 */
class SyncService
{
    use Debugable;

    private PDO $db;
    private array $data;
    private int $anio;
    private bool $debug;

    /**
     * Constructor
     * 
     * @param PDO $db Conexión a la base de datos
     * @param array $data Datos decodificados del JSON
     * @param int $anio Año de los festivos
     * @param bool $debug Activar modo depuración (muestra SQL)
     */
    public function __construct(PDO $db, array $data, int $anio = 2026, bool $debug = true)
    {
        $this->db = $db;
        $this->data = $data;
        $this->anio = $anio;
        $this->debug = $debug;
    }

    /**
     * Ejecuta la sincronización completa
     */
    public function sync(): void
    {
        try {
            $this->db->beginTransaction();
            LoggerService::log('BEGIN TRANSACTION', [], 'TRANSACTION');

            $this->syncComunidades();
            $this->syncProvincias();
            $this->syncMunicipios();
            $this->syncFestivos();

            $this->db->commit();
            LoggerService::log('COMMIT', [], 'TRANSACTION');
            echo "[OK] Sincronización completada.\n";

        } catch (Exception $e) {
            $this->db->rollBack();
            LoggerService::log('ROLLBACK', [], 'TRANSACTION');
            throw $e;
        }
    }

    /**
     * Ejecuta una consulta y la registra en el log
     * 
     * @param PDOStatement $stmt Sentencia preparada
     * @param array $params Parámetros
     * @param string $type Tipo de consulta
     * @return bool Resultado de la ejecución
     */
    private function executeAndLog(PDOStatement $stmt, array $params, string $type): bool
    {
        $startTime = microtime(true);
        $result = $stmt->execute($params);
        $executionTime = microtime(true) - $startTime;

        // Registrar en el log
        LoggerService::log($stmt->queryString, $params, $type, $executionTime);

        return $result;
    }

    // ============================================
    // MÉTODOS DE SINCRONIZACIÓN
    // ============================================

    /**
     * Sincroniza las comunidades autónomas
     * 
     * Extrae los datos del array 'regions' del JSON
     */
    private function syncComunidades(): void
    {
        echo "  - Insertando comunidades autónomas...\n";

        $stmt = $this->db->prepare("
            INSERT INTO comunidades_autonomas (codigo, nombre, activo) 
            VALUES (:codigo, :nombre, 1) 
            ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)
        ");

        // Obtener las regiones del JSON
        $regions = $this->data['regions'] ?? [];
        
        if (empty($regions)) {
            echo "    [WARNING] No se encontraron regiones en el JSON.\n";
            return;
        }

        $total = 0;

        foreach ($regions as $region) {
            $codigo = $region['code'] ?? '';
            $nombre = $region['name'] ?? '';
            
            // Saltar si no tiene código o nombre
            if (empty($codigo) || empty($nombre)) {
                continue;
            }

            $params = [
                ':codigo' => $codigo,
                ':nombre' => $nombre
            ];
            
            if ($this->debug) {
                $this->dumpSql($stmt, $params, '    [SQL] ');
            }
            
            $this->executeAndLog($stmt, $params, 'INSERT');
            $total++;
        }

        echo "    - Total de comunidades insertadas: {$total}\n";
    }

    /**
     * Sincroniza las provincias
     * 
     * Extrae los datos del array 'regions[].provinces' del JSON
     */
    private function syncProvincias(): void
    {
        echo "  - Insertando provincias...\n";

        // Obtener el mapa de comunidades (codigo -> id)
        $comunidadMap = $this->getComunidadMapByCode();

        $stmt = $this->db->prepare("
            INSERT INTO provincias (codigo, nombre, comunidad_id, activo) 
            VALUES (:codigo, :nombre, :comunidad_id, 1) 
            ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), comunidad_id = VALUES(comunidad_id)
        ");

        // Recorrer las regiones del JSON
        $regions = $this->data['regions'] ?? [];
        
        if (empty($regions)) {
            echo "    [WARNING] No se encontraron regiones en el JSON.\n";
            return;
        }

        $total = 0;

        foreach ($regions as $region) {
            $regionCode = $region['code'] ?? '';
            
            // Buscar el ID de la comunidad por su código
            $comunidadId = $comunidadMap[$regionCode] ?? null;
            
            if (!$comunidadId) {
                echo "    [WARNING] No se encontró la comunidad para el código: {$regionCode}\n";
                continue;
            }

            // Obtener las provincias de esta región
            $provinces = $region['provinces'] ?? [];
            
            if (empty($provinces)) {
                continue;
            }

            foreach ($provinces as $provincia) {
                $codigo = $provincia['code'] ?? '';
                $nombre = $provincia['name'] ?? '';
                
                // Saltar si no tiene código o nombre
                if (empty($codigo) || empty($nombre)) {
                    continue;
                }

                $params = [
                    ':codigo' => $codigo,
                    ':nombre' => $nombre,
                    ':comunidad_id' => $comunidadId
                ];
                
                if ($this->debug) {
                    $this->dumpSql($stmt, $params, '    [SQL] ');
                }
                
                $this->executeAndLog($stmt, $params, 'INSERT');
                $total++;
            }
        }

        echo "    - Total de provincias insertadas: {$total}\n";
    }

    /**
     * Sincroniza los municipios
     * 
     * Extrae los datos del array 'regions[].provinces[].municipalities' del JSON
     */
    private function syncMunicipios(): void
    {
        echo "  - Insertando municipios...\n";

        // Obtener el mapa de provincias (codigo -> id)
        $provinciaMap = $this->getProvinciaMapByCode();

        $stmt = $this->db->prepare("
            INSERT INTO municipios (codigo_ine, nombre, provincia_id, activo) 
            VALUES (:codigo_ine, :nombre, :provincia_id, 1) 
            ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), provincia_id = VALUES(provincia_id)
        ");

        // Recorrer las regiones del JSON
        $regions = $this->data['regions'] ?? [];
        
        if (empty($regions)) {
            echo "    [WARNING] No se encontraron regiones en el JSON.\n";
            return;
        }

        $total = 0;

        foreach ($regions as $region) {
            $provinces = $region['provinces'] ?? [];
            
            foreach ($provinces as $provincia) {
                $provinciaCodigo = $provincia['code'] ?? '';
                $provinciaId = $provinciaMap[$provinciaCodigo] ?? null;
                
                if (!$provinciaId) {
                    continue;
                }

                // Obtener los municipios de esta provincia
                $municipalities = $provincia['municipalities'] ?? [];
                
                if (empty($municipalities)) {
                    continue;
                }

                foreach ($municipalities as $municipio) {
                    $codigoIne = $municipio['ine'] ?? $municipio['code'] ?? '';
                    $nombre = $municipio['name'] ?? '';
                    
                    // Saltar si no tiene código o nombre
                    if (empty($codigoIne) || empty($nombre)) {
                        continue;
                    }

                    $params = [
                        ':codigo_ine' => $codigoIne,
                        ':nombre' => $nombre,
                        ':provincia_id' => $provinciaId
                    ];
                    
                    if ($this->debug) {
                        $this->dumpSql($stmt, $params, '    [SQL] ');
                    }
                    
                    $this->executeAndLog($stmt, $params, 'INSERT');
                    $total++;
                }
            }
        }

        echo "    - Total de municipios insertados: {$total}\n";
    }

    /**
     * Sincroniza los festivos
     * 
     * Extrae los datos de 'spain.holidays', 'regions[].holidays', 
     * 'regions[].provinces[].holidays' y 'regions[].provinces[].municipalities[].holidays'
     */
    private function syncFestivos(): void
    {
        echo "  - Insertando festivos...\n";

        // Obtener los mapas de códigos
        $comunidadMap = $this->getComunidadMapByCode();
        $provinciaMap = $this->getProvinciaMapByCode();
        $municipioMap = $this->getMunicipioMapByCode();

        $stmt = $this->db->prepare("
            INSERT INTO festivos (
                nombre, fecha, anio, tipo, 
                comunidad_id, provincia_id, municipio_id, 
                activo
            ) VALUES (
                :nombre, :fecha, :anio, :tipo,
                :comunidad_id, :provincia_id, :municipio_id,
                1
            )
            ON DUPLICATE KEY UPDATE 
                nombre = VALUES(nombre),
                fecha = VALUES(fecha),
                tipo = VALUES(tipo)
        ");

        $contador = 0;
        $mostrarCada = 50; // Mostrar SQL cada N registros

        // ============================================
        // 1. FESTIVOS NACIONALES (spain.holidays)
        // ============================================
        if (isset($this->data['spain']['holidays']) && is_array($this->data['spain']['holidays'])) {
            foreach ($this->data['spain']['holidays'] as $festivo) {
                $params = [
                    ':nombre' => $festivo['name'] ?? '',
                    ':fecha' => $festivo['date'] ?? "{$this->anio}-01-01",
                    ':anio' => $this->anio,
                    ':tipo' => 'Nacional',
                    ':comunidad_id' => null,
                    ':provincia_id' => null,
                    ':municipio_id' => null
                ];
                
                if ($this->debug && $contador % $mostrarCada === 0) {
                    $this->dumpSql($stmt, $params, '    [SQL] ');
                }
                
                $this->executeAndLog($stmt, $params, 'INSERT');
                $contador++;
            }
        }

        // ============================================
        // 2. RECORRER REGIONES PARA FESTIVOS AUTONÓMICOS, PROVINCIALES Y LOCALES
        // ============================================
        $regions = $this->data['regions'] ?? [];
        foreach ($regions as $region) {
            $comunidadCodigo = $region['code'] ?? '';
            $comunidadId = $comunidadMap[$comunidadCodigo] ?? null;
            
            if (!$comunidadId) {
                continue;
            }

            // ============================================
            // 2.1 FESTIVOS AUTONÓMICOS (regions[].holidays)
            // ============================================
            if (isset($region['holidays']) && is_array($region['holidays'])) {
                foreach ($region['holidays'] as $festivo) {
                    $params = [
                        ':nombre' => $festivo['name'] ?? '',
                        ':fecha' => $festivo['date'] ?? "{$this->anio}-01-01",
                        ':anio' => $this->anio,
                        ':tipo' => 'Autonómico',
                        ':comunidad_id' => $comunidadId,
                        ':provincia_id' => null,
                        ':municipio_id' => null
                    ];
                    
                    if ($this->debug && $contador % $mostrarCada === 0) {
                        $this->dumpSql($stmt, $params, '    [SQL] ');
                    }
                    
                    $this->executeAndLog($stmt, $params, 'INSERT');
                    $contador++;
                }
            }

            // ============================================
            // 2.2 RECORRER PROVINCIAS DE LA REGIÓN
            // ============================================
            $provinces = $region['provinces'] ?? [];
            foreach ($provinces as $provincia) {
                $provinciaCodigo = $provincia['code'] ?? '';
                $provinciaId = $provinciaMap[$provinciaCodigo] ?? null;
                
                if (!$provinciaId) {
                    continue;
                }

                // ============================================
                // 2.2.1 FESTIVOS PROVINCIALES (regions[].provinces[].holidays)
                // ============================================
                if (isset($provincia['holidays']) && is_array($provincia['holidays'])) {
                    foreach ($provincia['holidays'] as $festivo) {
                        $params = [
                            ':nombre' => $festivo['name'] ?? '',
                            ':fecha' => $festivo['date'] ?? "{$this->anio}-01-01",
                            ':anio' => $this->anio,
                            ':tipo' => 'Provincial',
                            ':comunidad_id' => null,
                            ':provincia_id' => $provinciaId,
                            ':municipio_id' => null
                        ];
                        
                        if ($this->debug && $contador % $mostrarCada === 0) {
                            $this->dumpSql($stmt, $params, '    [SQL] ');
                        }
                        
                        $this->executeAndLog($stmt, $params, 'INSERT');
                        $contador++;
                    }
                }

                // ============================================
                // 2.2.2 RECORRER MUNICIPIOS DE LA PROVINCIA
                // ============================================
                $municipalities = $provincia['municipalities'] ?? [];
                foreach ($municipalities as $municipio) {
                    $municipioCodigo = $municipio['ine'] ?? $municipio['code'] ?? '';
                    $municipioId = $municipioMap[$municipioCodigo] ?? null;
                    
                    if (!$municipioId) {
                        continue;
                    }

                    // ============================================
                    // 2.2.2.1 FESTIVOS LOCALES (regions[].provinces[].municipalities[].holidays)
                    // ============================================
                    if (isset($municipio['holidays']) && is_array($municipio['holidays'])) {
                        foreach ($municipio['holidays'] as $festivo) {
                            $params = [
                                ':nombre' => $festivo['name'] ?? '',
                                ':fecha' => $festivo['date'] ?? "{$this->anio}-01-01",
                                ':anio' => $this->anio,
                                ':tipo' => 'Local',
                                ':comunidad_id' => null,
                                ':provincia_id' => null,
                                ':municipio_id' => $municipioId
                            ];
                            
                            if ($this->debug && $contador % $mostrarCada === 0) {
                                $this->dumpSql($stmt, $params, '    [SQL] ');
                            }
                            
                            $this->executeAndLog($stmt, $params, 'INSERT');
                            $contador++;
                        }
                    }
                }
            }
        }

        echo "    - Total de festivos insertados: {$contador}\n";
    }

    // ============================================
    // MÉTODOS AUXILIARES PARA MAPAS DE CÓDIGOS
    // ============================================

    /**
     * Obtiene el mapa de comunidades (codigo -> id)
     */
    private function getComunidadMapByCode(): array
    {
        $map = [];
        $stmt = $this->db->query("SELECT id, codigo FROM comunidades_autonomas WHERE activo = 1");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $map[$row['codigo']] = (int)$row['id'];
        }
        return $map;
    }

    /**
     * Obtiene el mapa de provincias (codigo -> id)
     */
    private function getProvinciaMapByCode(): array
    {
        $map = [];
        $stmt = $this->db->query("SELECT id, codigo FROM provincias WHERE activo = 1");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $map[$row['codigo']] = (int)$row['id'];
        }
        return $map;
    }

    /**
     * Obtiene el mapa de municipios (codigo_ine -> id)
     */
    private function getMunicipioMapByCode(): array
    {
        $map = [];
        $stmt = $this->db->query("SELECT id, codigo_ine FROM municipios WHERE activo = 1");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $map[$row['codigo_ine']] = (int)$row['id'];
        }
        return $map;
    }
}