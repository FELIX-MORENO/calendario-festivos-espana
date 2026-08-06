<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Clase de ayuda para operaciones de seguridad
 * 
 * Proporciona métodos para encriptar y desencriptar datos
 * usando OpenSSL con AES-256-CBC.
 */
class SecurityHelper
{
    /**
     * Clave de encriptación (debe estar en .env)
     */
    private static string $key;

    /**
     * Inicializa la clave de encriptación
     */
    private static function getKey(): string
    {
        if (!isset(self::$key)) {
            // Obtener la clave desde .env o usar una por defecto (cambiar en producción)
            self::$key = $_ENV['ENCRYPTION_KEY'] ?? 'gestionfestivos-secret-key-2026-32chars';
            
            // Asegurar que la clave tiene 32 bytes (AES-256)
            self::$key = substr(hash('sha256', self::$key, true), 0, 32);
        }
        return self::$key;
    }

    /**
     * Encripta un dato
     * 
     * @param mixed $data Dato a encriptar
     * @return string Dato encriptado (base64)
     */
    public static function encrypt($data): string
    {
        $key = self::getKey();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        
        // Convertir a JSON para poder encriptar arrays/objetos
        $dataString = is_string($data) ? $data : json_encode($data);
        
        $encrypted = openssl_encrypt(
            $dataString,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Combinar IV + datos encriptados y codificar en base64
        return base64_encode($iv . $encrypted);
    }

    /**
     * Desencripta un dato
     * 
     * @param string $encrypted Dato encriptado (base64)
     * @return mixed Dato desencriptado
     */
    public static function decrypt(string $encrypted)
    {
        $key = self::getKey();
        $data = base64_decode($encrypted);
        
        // Extraer IV (primeros 16 bytes)
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $encryptedData = substr($data, $ivLength);
        
        $decrypted = openssl_decrypt(
            $encryptedData,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Intentar decodificar como JSON
        $jsonData = json_decode($decrypted, true);
        return $jsonData !== null ? $jsonData : $decrypted;
    }

    /**
     * Encripta un ID para usar en URLs o peticiones
     * 
     * @param int $id ID a encriptar
     * @return string ID encriptado
     */
    public static function encryptId(int $id): string
    {
        return self::encrypt($id);
    }

    /**
     * Desencripta un ID recibido desde el frontend
     * 
     * @param string $encryptedId ID encriptado
     * @return int ID desencriptado
     * @throws \InvalidArgumentException Si no se puede desencriptar
     */
    public static function decryptId(string $encryptedId): int
    {
        $decrypted = self::decrypt($encryptedId);
        
        if (!is_numeric($decrypted)) {
            throw new \InvalidArgumentException('ID encriptado inválido');
        }
        
        return (int) $decrypted;
    }

    /**
     * Genera una clave de encriptación aleatoria (para .env)
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(32));
    }
}