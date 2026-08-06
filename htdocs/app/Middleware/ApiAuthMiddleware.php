<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\ApiKeyModel;
use App\Services\LoggerService;

/**
 * Middleware para autenticación de API Key
 * 
 * Este middleware se ejecuta ANTES de que la petición llegue al controlador.
 * Su única responsabilidad es verificar que la API Key es válida.
 * 
 * Si es válida → permite que la petición continúe.
 * Si no es válida → devuelve error 401/403 y BLOQUEA la petición.
 */
class ApiAuthMiddleware
{
    /**
     * Maneja la autenticación de la API
     * 
     * @return bool True si la autenticación es correcta
     */
    public function handle(): bool
    {
        // ============================================
        // 1. OBTENER DATOS DE LA PETICIÓN (contexto común)
        // ============================================
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? 'unknown';

        // ============================================
        // 2. OBTENER LA API KEY
        // ============================================
        $apiKey = $this->getApiKeyFromRequest();

        if (empty($apiKey)) {
            // 📝 Log: Petición sin API Key
            LoggerService::logAuth(
                'PETICIÓN RECHAZADA: API Key no proporcionada',
                [
                    'ip' => $ip,
                    'method' => $method,
                    'uri' => $uri,
                    'origin' => $origin,
                ],
                'AUTH_ERROR'
            );
            //API Key requerida
            $this->sendError('Error parametros', 401);
            return false;
        }

        // ============================================
        // 3. BUSCAR LA CLAVE EN LA BASE DE DATOS
        // ============================================
        $apiKeyModel = new ApiKeyModel();
        $keyData = $apiKeyModel->getByKey($apiKey);

        if (!$keyData) {
            // 📝 Log: API Key inválida (no existe en BD)
            LoggerService::logAuth(
                'PETICIÓN RECHAZADA: API Key inválida',
                [
                    'api_key_provided' => substr($apiKey, 0, 10) . '...' . substr($apiKey, -6),
                    'ip' => $ip,
                    'method' => $method,
                    'uri' => $uri,
                    'origin' => $origin,
                ],
                'AUTH_ERROR'
            );
            //API Key inválida
            $this->sendError('Error parametros', 401);
            return false;
        }

        // ============================================
        // 4. VERIFICAR QUE LA CLAVE ESTÁ ACTIVA
        // ============================================
        if ($keyData['activo'] != 1) {
            LoggerService::logAuth(
                'PETICIÓN RECHAZADA: API Key revocada',
                [
                    'api_key_id' => $keyData['id'],
                    'api_key_nombre' => $keyData['nombre'],
                    'api_key_dominio' => $keyData['dominio_permitido'],
                    'ip' => $ip,
                    'method' => $method,
                    'uri' => $uri,
                    'origin' => $origin,
                ],
                'AUTH_ERROR'
            );
            //API Key revocada
            $this->sendError('Error parametros', 401);
            return false;
        }

        // ============================================
        // 5. VERIFICAR QUE LA CLAVE NO HA EXPIRADO
        // ============================================
        if ($keyData['expira_en'] && strtotime($keyData['expira_en']) < time()) {
            LoggerService::logAuth(
                'PETICIÓN RECHAZADA: API Key expirada',
                [
                    'api_key_id' => $keyData['id'],
                    'api_key_nombre' => $keyData['nombre'],
                    'api_key_dominio' => $keyData['dominio_permitido'],
                    'expira_en' => $keyData['expira_en'],
                    'ip' => $ip,
                    'method' => $method,
                    'uri' => $uri,
                    'origin' => $origin,
                ],
                'AUTH_ERROR'
            );
            //API Key expirada
            $this->sendError('Error parametros', 401);
            return false;
        }

        // ============================================
        // 6. VERIFICAR QUE EL DOMINIO ORIGEN ESTÁ PERMITIDO
        // ============================================
        $dominioPermitido = $keyData['dominio_permitido'] ?? '';

        if (!$this->isDomainAllowed($origin, $dominioPermitido)) {
            LoggerService::logAuth(
                'PETICIÓN RECHAZADA: Dominio no autorizado',
                [
                    'api_key_id' => $keyData['id'],
                    'api_key_nombre' => $keyData['nombre'],
                    'api_key_dominio' => $dominioPermitido,
                    'origin' => $origin,
                    'ip' => $ip,
                    'method' => $method,
                    'uri' => $uri,
                ],
                'AUTH_ERROR'
            );
            //Dominio no autorizado
            $this->sendError('Error parametros '.$dominioPermitido." - ".$origin, 403);
            return false;
        }

        // ============================================
        // 7. ACTUALIZAR ÚLTIMO USO
        // ============================================
        $apiKeyModel->updateLastUse($keyData['id']);

        // ============================================
        // 8. 📝 LOG DE ÉXITO (con todos los detalles)
        // ============================================
        LoggerService::logAuth(
            'PETICIÓN AUTORIZADA: API Key válida',
            [
                'api_key_id' => $keyData['id'],
                'api_key_nombre' => $keyData['nombre'],
                'api_key_dominio' => $dominioPermitido,
                'ip' => $ip,
                'method' => $method,
                'uri' => $uri,
                'origin' => $origin,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ],
            'AUTH_SUCCESS'
        );

        // ============================================
        // 9. GUARDAR INFORMACIÓN PARA EL CONTROLADOR
        // ============================================
        $_SERVER['API_KEY_ID'] = $keyData['id'];
        $_SERVER['API_KEY_NOMBRE'] = $keyData['nombre'];

        return true;
    }

    /**
     * Obtiene la API Key del header de la petición
     * 
     * @return string|null
     */
    private function getApiKeyFromRequest(): ?string
    {
        // ============================================
        // 🔥 MÉTODO COMPATIBLE CON TODOS LOS SERVIDORES
        // ============================================
        
        // 1. Buscar en el header X-API-Key (usando $_SERVER)
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            return $_SERVER['HTTP_X_API_KEY'];
        }
        
        // 2. Buscar en Authorization: Bearer <token>
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            if (strpos($auth, 'Bearer ') === 0) {
                return substr($auth, 7);
            }
        }
        
        // 3. Alternativa: buscar en getallheaders() (si existe)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            
            if (isset($headers['X-API-Key'])) {
                return $headers['X-API-Key'];
            }
            
            if (isset($headers['Authorization'])) {
                $auth = $headers['Authorization'];
                if (strpos($auth, 'Bearer ') === 0) {
                    return substr($auth, 7);
                }
            }
        }
        
        // 4. Buscar en $_ENV (por si acaso)
        if (isset($_ENV['API_KEY'])) {
            return $_ENV['API_KEY'];
        }
        
        return null;
    }

    /**
     * Verifica si el dominio origen está permitido
     * 
     * @param string $origin Dominio origen de la petición
     * @param string $dominioPermitido Dominio permitido para esta clave
     * @return bool
     */
    private function isDomainAllowed(string $origin, string $dominioPermitido): bool
    {
        // Si no hay origen (petición desde servidor, CLI, etc.), permitir
        if (empty($origin)) {
            return true;
        }

        // Extraer el dominio del origen (eliminar protocolo y ruta)
        $parsedOrigin = parse_url($origin);
        $domain = $parsedOrigin['host'] ?? $origin;
        
        // Eliminar 'www.' si existe para comparación
        $domain = preg_replace('/^www\./', '', $domain);
        $dominioPermitido = preg_replace('/^www\./', '', $dominioPermitido);
        
        // Comparación exacta
        return $domain === $dominioPermitido;
    }

    /**
     * Envía una respuesta de error en formato JSON
     * 
     * @param string $message Mensaje de error
     * @param int $statusCode Código HTTP
     */
    private function sendError(string $message, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $statusCode
        ]);
        exit;
    }
}