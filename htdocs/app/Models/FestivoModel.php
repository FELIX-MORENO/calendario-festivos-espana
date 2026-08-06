<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;

/**
 * Modelo para la gestión de festivos
 * 
 * Esta clase maneja todas las operaciones relacionadas con la tabla 'festivos'
 * utilizando exclusivamente sentencias preparadas para prevenir inyección SQL.
 */
class FestivoModel extends Model
{
    /**
     * Nombre de la tabla asociada al modelo
     */
    protected string $table = 'festivos';

    /**
     * Campos permitidos para inserción/actualización masiva
     */
    protected array $fillable = [
        'nombre',
        'fecha',
        'anio',
        'tipo',
        'comunidad_id',
        'provincia_id',
        'municipio_id',
        'activo',
        'creado_por',
        'modificado_por'
    ];

    /**
     * Constructor: Inicializa la conexión a la base de datos
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Inserta o actualiza un festivo utilizando "UPSERT"
     * (INSERT ... ON DUPLICATE KEY UPDATE)
     * 
     * @param array $data Datos del festivo
     * @param int $creadoPor ID del usuario que crea/modifica
     * @return int ID del festivo insertado o actualizado
     * @throws PDOException
     */
    public function upsertFestivo(array $data, int $creadoPor = 1): int
    {
        // Validar que los datos requeridos estén presentes
        $requiredFields = ['nombre', 'fecha', 'anio', 'tipo'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("El campo '{$field}' es obligatorio.");
            }
        }

        // Construir la consulta SQL con marcadores nombrados
        $sql = "INSERT INTO {$this->table} (
                    nombre, 
                    fecha, 
                    anio, 
                    tipo, 
                    comunidad_id, 
                    provincia_id, 
                    municipio_id,
                    creado_por
                ) VALUES (
                    :nombre,
                    :fecha,
                    :anio,
                    :tipo,
                    :comunidad_id,
                    :provincia_id,
                    :municipio_id,
                    :creado_por
                ) ON DUPLICATE KEY UPDATE
                    nombre = VALUES(nombre),
                    fecha = VALUES(fecha),
                    modificado_por = VALUES(creado_por),
                    modificado_en = NOW()";

        // Preparar la sentencia
        $stmt = $this->db->prepare($sql);

        // Vincular parámetros
        $stmt->bindValue(':nombre', $data['nombre'], PDO::PARAM_STR);
        $stmt->bindValue(':fecha', $data['fecha'], PDO::PARAM_STR);
        $stmt->bindValue(':anio', $data['anio'], PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
        
        // Relaciones: pueden ser NULL
        $stmt->bindValue(':comunidad_id', $data['comunidad_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':provincia_id', $data['provincia_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':municipio_id', $data['municipio_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':creado_por', $creadoPor, PDO::PARAM_INT);

        // Ejecutar la sentencia
        $stmt->execute();

        // Obtener el ID del registro insertado o actualizado
        if ($this->db->lastInsertId()) {
            return (int) $this->db->lastInsertId();
        }

        // Si no se insertó (porque se actualizó), obtener el ID existente
        $sqlSelect = "SELECT id FROM {$this->table} 
                      WHERE fecha = :fecha 
                        AND tipo = :tipo 
                        AND anio = :anio 
                        AND (comunidad_id <=> :comunidad_id)
                        AND (provincia_id <=> :provincia_id)
                        AND (municipio_id <=> :municipio_id)";
        
        $stmtSelect = $this->db->prepare($sqlSelect);
        $stmtSelect->bindValue(':fecha', $data['fecha'], PDO::PARAM_STR);
        $stmtSelect->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
        $stmtSelect->bindValue(':anio', $data['anio'], PDO::PARAM_INT);
        $stmtSelect->bindValue(':comunidad_id', $data['comunidad_id'] ?? null, PDO::PARAM_INT);
        $stmtSelect->bindValue(':provincia_id', $data['provincia_id'] ?? null, PDO::PARAM_INT);
        $stmtSelect->bindValue(':municipio_id', $data['municipio_id'] ?? null, PDO::PARAM_INT);
        $stmtSelect->execute();

        $result = $stmtSelect->fetch(PDO::FETCH_ASSOC);
        return $result ? (int) $result['id'] : 0;
    }

    /**
     * Obtiene todos los festivos de un municipio para un año específico
     * 
     * @param int $municipioId ID del municipio
     * @param int $anio Año a consultar
     * @return array Lista de festivos ordenados por fecha
     */
    public function getFestivosByMunicipioAndAnio(int $municipioId, int $anio): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE municipio_id = :municipio_id
                  AND anio = :anio
                  AND activo = 1
                ORDER BY fecha ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':municipio_id', $municipioId, PDO::PARAM_INT);
        $stmt->bindValue(':anio', $anio, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los festivos de una provincia para un año específico
     * 
     * @param int $provinciaId ID de la provincia
     * @param int $anio Año a consultar
     * @return array Lista de festivos ordenados por fecha
     */
    public function getFestivosByProvinciaAndAnio(int $provinciaId, int $anio): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE provincia_id = :provincia_id
                  AND anio = :anio
                  AND activo = 1
                ORDER BY fecha ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':provincia_id', $provinciaId, PDO::PARAM_INT);
        $stmt->bindValue(':anio', $anio, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los festivos de una comunidad autónoma para un año específico
     * 
     * @param int $comunidadId ID de la comunidad autónoma
     * @param int $anio Año a consultar
     * @return array Lista de festivos ordenados por fecha
     */
    public function getFestivosByComunidadAndAnio(int $comunidadId, int $anio): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE comunidad_id = :comunidad_id
                  AND anio = :anio
                  AND activo = 1
                ORDER BY fecha ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':comunidad_id', $comunidadId, PDO::PARAM_INT);
        $stmt->bindValue(':anio', $anio, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los festivos nacionales para un año específico
     * 
     * @param int $anio Año a consultar
     * @return array Lista de festivos nacionales ordenados por fecha
     */
    public function getFestivosNacionalesByAnio(int $anio): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE tipo = 'Nacional'
                  AND anio = :anio
                  AND activo = 1
                ORDER BY fecha ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':anio', $anio, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene TODOS los festivos de un municipio para un año específico
     * (Incluye nacionales, autonómicos, provinciales y locales)
     * 
     * Este método es el que debe usarse para la vista pública y la API,
     * ya que recopila todos los festivos que aplican a un municipio
     * independientemente de su nivel (Nacional, Autonómico, Provincial, Local).
     * 
     * @param int $municipioId ID del municipio
     * @param int $anio Año a consultar
     * @return array Lista de festivos ordenados por fecha
     */
    public function getFestivosCompletosByMunicipioAndAnio(int $municipioId, int $anio): array
    {
        // Primero obtener la provincia y comunidad del municipio
        $sqlInfo = "
            SELECT 
                m.id AS municipio_id,
                m.provincia_id,
                p.comunidad_id
            FROM municipios m
            INNER JOIN provincias p ON m.provincia_id = p.id
            WHERE m.id = :municipio_id
        ";
        
        $stmtInfo = $this->db->prepare($sqlInfo);
        $stmtInfo->bindValue(':municipio_id', $municipioId, PDO::PARAM_INT);
        $stmtInfo->execute();
        $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        if (!$info) {
            return [];
        }

        $comunidadId = $info['comunidad_id'];
        $provinciaId = $info['provincia_id'];

        // Obtener todos los festivos que aplican a este municipio
        $sql = "
            SELECT 
                f.id,
                f.nombre,
                f.fecha,
                f.tipo,
                f.anio,
                CASE 
                    WHEN f.tipo = 'Nacional' THEN 'Nacional'
                    WHEN f.tipo = 'Autonómico' THEN 'Autonómico'
                    WHEN f.tipo = 'Provincial' THEN 'Provincial'
                    WHEN f.tipo = 'Local' THEN 'Local'
                END AS nivel
            FROM 
                festivos f
            WHERE 
                f.anio = :anio
                AND f.activo = 1
                AND (
                    -- Festivos NACIONALES (aplican a todos)
                    f.tipo = 'Nacional'
                    -- Festivos AUTONÓMICOS (de la comunidad del municipio)
                    OR (f.tipo = 'Autonómico' AND f.comunidad_id = :comunidad_id)
                    -- Festivos PROVINCIALES (de la provincia del municipio)
                    OR (f.tipo = 'Provincial' AND f.provincia_id = :provincia_id)
                    -- Festivos LOCALES (del propio municipio)
                    OR (f.tipo = 'Local' AND f.municipio_id = :municipio_id)
                )
            ORDER BY 
                f.fecha ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':municipio_id', $municipioId, PDO::PARAM_INT);
        $stmt->bindValue(':anio', $anio, PDO::PARAM_INT);
        $stmt->bindValue(':comunidad_id', $comunidadId, PDO::PARAM_INT);
        $stmt->bindValue(':provincia_id', $provinciaId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Obtiene TODOS los festivos de una comunidad autónoma para un año específico
     * (Incluye nacionales, autonómicos y provinciales de la comunidad)
     * 
     * @param int $comunidadId ID de la comunidad autónoma
     * @param int $anio Año a consultar
     * @return array Lista de festivos ordenados por fecha
     */
    public function getFestivosCompletosByComunidadAutonomaAndAnio(int $comunidadId, int $anio): array
    {
        $sql = "
            SELECT 
                f.id,
                f.nombre,
                f.fecha,
                f.tipo,
                f.anio,
                CASE 
                    WHEN f.tipo = 'Nacional' THEN 'Nacional'
                    WHEN f.tipo = 'Autonómico' THEN 'Autonómico'
                END AS nivel
            FROM 
                festivos f
            WHERE 
                f.anio = :anio
                AND f.activo = 1
                AND (
                    f.tipo = 'Nacional'
                    OR (f.tipo = 'Autonómico' AND f.comunidad_id = :comunidad_id)
                )
            ORDER BY 
                f.fecha ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':anio', $anio, PDO::PARAM_INT);
        $stmt->bindValue(':comunidad_id', $comunidadId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    }

    /**
     * Obtiene TODOS los festivos nacionales
     * 
     * @param int $anio Año a consultar
     * @return array Lista de festivos ordenados por fecha
     */
    public function getFestivosCompletosByAnio(int $anio): array
    {
        $sql = "
            SELECT 
                f.id,
                f.nombre,
                f.fecha,
                f.tipo,
                f.anio,
                CASE 
                    WHEN f.tipo = 'Nacional' THEN 'Nacional'
                END AS nivel
            FROM 
                festivos f
            WHERE 
                f.anio = :anio
                AND f.activo = 1
                AND (
                    f.tipo = 'Nacional'
                )
            ORDER BY 
                f.fecha ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':anio', $anio, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    }

    /**
     * Verifica si un festivo ya existe en la base de datos
     * 
     * @param array $data Datos del festivo (fecha, tipo, anio, relaciones)
     * @return bool True si existe, False si no
     */
    public function exists(array $data): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE fecha = :fecha
                  AND tipo = :tipo
                  AND anio = :anio
                  AND (comunidad_id <=> :comunidad_id)
                  AND (provincia_id <=> :provincia_id)
                  AND (municipio_id <=> :municipio_id)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':fecha', $data['fecha'], PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
        $stmt->bindValue(':anio', $data['anio'], PDO::PARAM_INT);
        $stmt->bindValue(':comunidad_id', $data['comunidad_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':provincia_id', $data['provincia_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':municipio_id', $data['municipio_id'] ?? null, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Elimina lógicamente un festivo (activo = 0)
     * 
     * @param int $id ID del festivo
     * @param int $modificadoPor ID del usuario que elimina
     * @return bool True si se eliminó correctamente
     */
    public function softDelete(int $id, int $modificadoPor): bool
    {
        $sql = "UPDATE {$this->table}
                SET activo = 0,
                    modificado_por = :modificado_por,
                    modificado_en = NOW()
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':modificado_por', $modificadoPor, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Reactiva un festivo (activo = 1)
     * 
     * @param int $id ID del festivo
     * @param int $modificadoPor ID del usuario que reactiva
     * @return bool True si se reactivó correctamente
     */
    public function reactivate(int $id, int $modificadoPor): bool
    {
        $sql = "UPDATE {$this->table}
                SET activo = 1,
                    modificado_por = :modificado_por,
                    modificado_en = NOW()
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':modificado_por', $modificadoPor, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Obtiene todos los años disponibles en la tabla
     * 
     * @return array Lista de años con festivos
     */
    public function getAvailableYears(): array
    {
        $sql = "SELECT DISTINCT anio FROM {$this->table}
                WHERE activo = 1
                ORDER BY anio DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Obtiene el total de festivos por año
     * 
     * @param int $anio Año a consultar
     * @return int Número de festivos en ese año
     */
    public function countByYear(int $anio): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE anio = :anio AND activo = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':anio', $anio, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}