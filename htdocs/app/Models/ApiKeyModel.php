<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Modelo para la gestión de API Keys
 * 
 * Maneja la tabla 'api_keys' con dominio único por clave.
 */
class ApiKeyModel extends Model
{
    protected string $table = 'api_keys';

    /**
     * Genera una nueva API Key
     * 
     * @param string $nombre Nombre descriptivo
     * @param string $dominio Dominio permitido (único)
     * @param int|null $expiraEn Días hasta expiración (null = sin expiración)
     * @param int $creadoPor ID del usuario que crea
     * @return string API Key generada
     */
    public function generarApiKey(
        string $nombre,
        string $dominio,
        ?int $expiraEn = null,
        int $creadoPor = 1
    ): string {
        // Generar clave aleatoria (64 caracteres hexadecimales)
        $apiKey = bin2hex(random_bytes(32));
        
        // Calcular fecha de expiración si se especifica
        $expiraEnDate = null;
        if ($expiraEn !== null && $expiraEn > 0) {
            $expiraEnDate = date('Y-m-d H:i:s', strtotime("+{$expiraEn} days"));
        }
        
        // Insertar en la base de datos
        $sql = "INSERT INTO {$this->table} 
                (nombre, api_key, dominio_permitido, expira_en, creado_por) 
                VALUES (:nombre, :api_key, :dominio, :expira_en, :creado_por)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':api_key' => $apiKey,
            ':dominio' => $dominio,
            ':expira_en' => $expiraEnDate,
            ':creado_por' => $creadoPor
        ]);
        
        return $apiKey;
    }

    /**
     * Obtiene una API Key por su clave
     * 
     * @param string $apiKey Clave a buscar
     * @return array|null Datos de la clave o null si no existe
     */
    public function getByKey(string $apiKey): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE api_key = :api_key";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':api_key', $apiKey, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Actualiza la fecha de último uso de una API Key
     * 
     * @param int $id ID de la API Key
     * @return bool
     */
    public function updateLastUse(int $id): bool
    {
        $sql = "UPDATE {$this->table} 
                SET ultimo_uso = NOW() 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Revoca una API Key (activo = 0)
     * 
     * @param int $id ID de la API Key
     * @param int $modificadoPor ID del usuario que revoca
     * @return bool
     */
    public function revocar(int $id, int $modificadoPor): bool
    {
        return $this->softDelete($id, $modificadoPor);
    }

    /**
     * Obtiene todas las API Keys activas
     * 
     * @return array
     */
    public function getActiveKeys(): array
    {
        return $this->getAll();
    }

    /**
     * Verifica si un dominio ya tiene una API Key activa
     * 
     * @param string $dominio Dominio a verificar
     * @return bool True si ya existe una clave para ese dominio
     */
    public function existsDomain(string $dominio): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE dominio_permitido = :dominio AND activo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':dominio', $dominio, PDO::PARAM_STR);
        $stmt->execute();
        
        return (int) $stmt->fetchColumn() > 0;
    }
}