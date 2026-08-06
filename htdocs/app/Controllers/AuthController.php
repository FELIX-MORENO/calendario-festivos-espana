<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador de autenticación
 * 
 * Maneja el login, logout y registro de administradores.
 */
class AuthController extends Controller
{
    /**
     * Muestra el formulario de login
     */
    public function login(): void
    {
        // Si ya está logueado, redirigir al dashboard
        if (isset($_SESSION['usuario_id'])) {
            $this->redirect('/admin');
        }

        $this->render('admin/login', [
            'titulo' => 'Iniciar Sesión',
            'error' => $_SESSION['login_error'] ?? null
        ]);
        
        // Limpiar el error después de mostrarlo
        unset($_SESSION['login_error']);
    }

    /**
     * Procesa el formulario de login
     */
    public function doLogin(): void
    {
        // TODO: Implementar validación de credenciales
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_nombre'] = 'Admin';
        $_SESSION['usuario_rol'] = 'admin';
        
        $this->redirect('/admin');
    }

    /**
     * Cierra la sesión del usuario
     */
    public function logout(): void
    {
        session_destroy();
        $this->redirect('/admin/login');
    }
}