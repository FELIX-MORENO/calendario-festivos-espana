<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador del panel de administración
 * 
 * Maneja todas las operaciones CRUD del panel admin.
 */
class AdminController extends Controller
{
    /**
     * Panel de administración (dashboard)
     */
    public function dashboard(): void
    {
        // Verificar que el usuario está logueado
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect('/admin/login');
        }

        $this->render('admin/dashboard', [
            'titulo' => 'Panel de Administración',
            'usuario' => $_SESSION['usuario_nombre'] ?? 'Administrador'
        ]);
    }

    /**
     * Gestión de municipios
     */
    public function municipios(): void
    {
        $this->render('admin/municipios', [
            'titulo' => 'Gestión de Municipios'
        ]);
    }

    /**
     * Gestión de festivos
     */
    public function festivos(): void
    {
        $this->render('admin/festivos', [
            'titulo' => 'Gestión de Festivos'
        ]);
    }

    /**
     * Gestión de API Keys
     */
    public function apiKeys(): void
    {
        $this->render('admin/api_keys', [
            'titulo' => 'Gestión de API Keys'
        ]);
    }

    /**
     * Visualización de logs
     */
    public function logs(): void
    {
        $this->render('admin/logs', [
            'titulo' => 'Logs del Sistema'
        ]);
    }
}