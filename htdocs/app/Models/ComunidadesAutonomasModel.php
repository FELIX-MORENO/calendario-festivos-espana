<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Modelo para la gestión de municipios
 * 
 * Maneja la tabla 'municipios'
 */
class ComunidadesAutonomasModel extends Model
{
    /**
     * Nombre de la tabla asociada al modelo
     */
    protected string $table = 'comunidades_autonomas';

    /**
     * Constructor: Inicializa la conexión a la base de datos
     */
    public function __construct()
    {
        parent::__construct();
    }



    /**
     * Obtiene el nombre de un municipio por su ID
     * 
     * @param int $id ID del municipio
     * @return string|null Nombre del municipio o null si no existe
     */
    public function getNombreById(int $id): ?string
    {
        $sql = "SELECT nombre FROM {$this->table}
                WHERE id = :id AND activo = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['nombre'] : null;
    }

    /**
     * Obtiene municipios con su provincia y comunidad
     * (Para vistas que necesitan información completa)
     * 
     * @return array Lista de municipios con datos de provincia y comunidad
     */
    public function getWithProvinciaAndComunidad(): array
    {
        $sql = "
            SELECT 
                m.id,
                m.codigo_ine,
                m.nombre AS municipio,
                p.nombre AS provincia,
                c.nombre AS comunidad
            FROM {$this->table} m
            INNER JOIN provincias p ON m.provincia_id = p.id
            INNER JOIN comunidades_autonomas c ON p.comunidad_id = c.id
            WHERE m.activo = 1
            ORDER BY m.nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}