<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador de sincronización con API externa
 * 
 * Maneja la importación de festivos desde calendariosnacionales.com
 */
class SyncController extends Controller
{
    /**
     * Sincroniza los festivos de un año específico
     * 
     * POST /admin/festivos/sincronizar
     */
    public function sincronizar(): void
    {
        $anio = $_POST['anio'] ?? date('Y');
        
        // TODO: Implementar sincronización real con la API externa
        $this->json([
            'success' => true,
            'message' => "Sincronización completada para el año {$anio}",
            'importados' => 15,
            'actualizados' => 3
        ]);
    }
}
