<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Controlador base de la aplicación
 * 
 * Todos los controladores deben heredar de esta clase.
 */
abstract class Controller
{
    /**
     * Renderiza una vista con datos
     * 
     * @param string $view Ruta de la vista (ej: 'public/index')
     * @param array $data Datos a pasar a la vista
     * @param string|null $layout Layout a usar (ej: 'layouts/header')
     * @throws \Exception Si la vista no existe
     */
    protected function render(string $view, array $data = [], string $layout = null): void
    {
        // Extraer los datos para que estén disponibles como variables en la vista
        extract($data);

        // Determinar la ruta completa de la vista
        $viewPath = __DIR__ . "/../Views/{$view}.php";
        
        // Verificar que la vista existe
        if (!file_exists($viewPath)) {
            throw new \Exception("Vista no encontrada: {$view} (buscando en: {$viewPath})");
        }

        // Incluir el header si se especifica un layout
        if ($layout) {
            $layoutPath = __DIR__ . "/../Views/{$layout}.php";
            if (file_exists($layoutPath)) {
                require_once $layoutPath;
            }
        }

        // Incluir la vista
        require_once $viewPath;

        // Incluir el footer si se especifica un layout
        if ($layout) {
            $footerPath = __DIR__ . "/../Views/layouts/footer.php";
            if (file_exists($footerPath)) {
                require_once $footerPath;
            }
        }
    }

    /**
     * Redirige a una URL
     */
    protected function redirect_BK(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    protected function redirect(string $url, int $statusCode = 302): void
    {
        $baseUrl = $_ENV['BASE_URL'] ?? '';
        $fullUrl = $baseUrl ? '/' . trim($baseUrl, '/') . '/' . ltrim($url, '/') : '/' . ltrim($url, '/');
        
        http_response_code($statusCode);
        header('Location: ' . $fullUrl);
        exit;
    }

    /**
     * Devuelve una respuesta JSON
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Devuelve un error 404
     */
    protected function notFound(): void
    {
        http_response_code(404);
        echo "<h1>404 - Página no encontrada</h1>";
        exit;
    }

    /**
     * Devuelve un error 403 (Prohibido)
     */
    protected function forbidden(): void
    {
        http_response_code(403);
        echo "<h1>403 - Acceso prohibido</h1>";
        exit;
    }
}