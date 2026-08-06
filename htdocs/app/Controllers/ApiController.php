<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MunicipioModel;
use App\Models\ComunidadesAutonomasModel;
use App\Models\FestivoModel;
use App\Helpers\SecurityHelper;

/**
 * Controlador de la API REST
 * 
 * Maneja los endpoints públicos y privados de la API.
 */
class ApiController extends Controller
{
    /**
     * Obtiene los festivos de un municipio (recibe ID encriptado)
     * 
     * GET /api/v1/festivos?municipio_id=<encriptado>&anio=2026
     */
    public function getFestivosMunicipio(): void
    {
        $encryptedMunicipioId = $_GET['municipio_id'] ?? null;
        $anio = $_GET['anio'] ?? date('Y');

        if (!$encryptedMunicipioId) {
            $this->json(['error' => 'municipio_id es requerido'], 400);
            return;
        }

        try {
            // ✅ Desencriptar el ID recibido
            $municipioId = SecurityHelper::decryptId($encryptedMunicipioId);
            
            if ($municipioId <= 0) {
                throw new \InvalidArgumentException('ID de municipio inválido');
            }

            $festivoModel = new FestivoModel();
            $festivos = $festivoModel->getFestivosCompletosByMunicipioAndAnio(
                $municipioId,
                (int)$anio
            );

            $this->json([
                'success' => true,
                'data' => $festivos,
                'total' => count($festivos),
                'municipio_id' => $encryptedMunicipioId,  // Devolvemos el ID encriptado
                'anio' => (int)$anio
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'error' => 'ID de municipio inválido: ' . $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Error al obtener los festivos: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Obtiene los festivos de un municipio (recibe ID encriptado)
     * 
     * GET /api/v1/festivos?comunidad_id=<encriptado>&anio=2026
     */
    public function getFestivosComunidadAutonoma(): void
    {
        $encryptedMunicipioId = $_GET['comunidad_id'] ?? null;
        $anio = $_GET['anio'] ?? date('Y');


        if (!$encryptedMunicipioId) {
            $this->json(['error' => 'comunidad_id es requerido'], 400);
            return;
        }

        try {
            // ✅ Desencriptar el ID recibido
            $municipioId = SecurityHelper::decryptId($encryptedMunicipioId);
            
            if ($municipioId <= 0) {
                throw new \InvalidArgumentException('ID de comunidad autonoma inválido');
            }


            $festivoModel = new FestivoModel();
            $festivos = $festivoModel->getFestivosCompletosByComunidadAutonomaAndAnio(
                $municipioId,
                (int)$anio
            );

            $this->json([
                'success' => true,
                'data' => $festivos,
                'total' => count($festivos),
                'municipio_id' => $encryptedMunicipioId,  // Devolvemos el ID encriptado
                'anio' => (int)$anio
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'error' => 'ID de municipio inválido: ' . $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Error al obtener los festivos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene los festivos nacionales
     * 
     * GET /api/v1/festivos?anio=2026
     */
    public function getFestivosNacionales(): void
    {
        $anio = $_GET['anio'] ?? date('Y');

        try {

            $festivoModel = new FestivoModel();
            $festivos = $festivoModel->getFestivosCompletosByAnio(
                (int)$anio
            );

            $this->json([
                'success' => true,
                'data' => $festivos,
                'total' => count($festivos),
                'anio' => (int)$anio
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'error' => 'ID de municipio inválido: ' . $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Error al obtener los festivos: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Obtiene el nivel legible a partir del tipo de festivo
     * 
     * @param string $tipo
     * @return string
     */
    private function getNivelFromTipo(string $tipo): string
    {
        $map = [
            'Nacional' => 'Nacional',
            'Autonómico' => 'Autonómico',
            'Provincial' => 'Provincial',
            'Local' => 'Local'
        ];
        return $map[$tipo] ?? $tipo;
    }

    /**
     * Obtiene la lista de municipios (con ID encriptado)
     * 
     * GET /api/v1/municipios
     */
    public function getMunicipios(): void
    {
        try {
            $municipioModel = new MunicipioModel();
            $municipios = $municipioModel->getAll('nombre', 'ASC');

            if (empty($municipios)) {
                $this->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0
                ]);
                return;
            }

            // ✅ Encriptar el ID de cada municipio
            $data = array_map(function($m) {
                return [
                    'id' => SecurityHelper::encryptId((int)$m['id']),  // 🔒 ID encriptado
                    'nombre' => $m['nombre'],
                    'codigo_ine' => $m['codigo_ine'] ?? null
                ];
            }, $municipios);

            $this->json([
                'success' => true,
                'data' => $data,
                'total' => count($data)
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Error al obtener los municipios: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Obtiene la lista de muncomunidades autonomas (con ID encriptado)
     * 
     * GET /api/v1/comunidades-autonomas
     */
    public function getComunidadesAutonomas(): void
    {
        try {
            $comunidadesAutonomasModel = new ComunidadesAutonomasModel();
            $comunidadesAutonomas = $comunidadesAutonomasModel->getAll('nombre', 'ASC');

            if (empty($comunidadesAutonomas)) {
                $this->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0
                ]);
                return;
            }

            // ✅ Encriptar el ID de cada municipio
            $data = array_map(function($m) {
                return [
                    'id' => SecurityHelper::encryptId((int)$m['id']),  // 🔒 ID encriptado
                    'nombre' => $m['nombre'],
                    'codigo_ine' => $m['codigo_ine'] ?? null
                ];
            }, $comunidadesAutonomas);

            $this->json([
                'success' => true,
                'data' => $data,
                'total' => count($data)
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Error al obtener los municipios: ' . $e->getMessage()
            ], 500);
        }
    }





    /**
     * Busca municipios por nombre
     * 
     * GET /api/v1/municipios/buscar?term=Mad
     */
    public function buscarMunicipios(): void
    {
        $term = $_GET['term'] ?? '';
        
        // TODO: Implementar búsqueda real
        $this->json([
            'success' => true,
            'data' => [
                ['id' => 1, 'nombre' => 'Madrid']
            ]
        ]);
    }
}
