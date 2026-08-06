<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class PublicController extends Controller
{
    public function index(): void
    {
        $this->render('public/index', [
            'titulo' => 'Calendario de Festivos',
            'descripcion' => 'Consulta los días festivos de cualquier municipio de España de forma rápida y sencilla.',
            'anio_actual' => date('Y')
        ]);
    }

    public function getMunicipios(): void
    {
        $this->render('public/index-municipios', [
            'titulo' => 'Calendario de Festivos',
            'descripcion' => 'Consulta los días festivos de cualquier municipio de España de forma rápida y sencilla.',
            'anio_actual' => date('Y')
        ]);
    }

    public function getComunidadesAutonomas(): void
    {
        $this->render('public/index-comunidades-autonomas', [
            'titulo' => 'Calendario de Festivos',
            'descripcion' => 'Consulta los días festivos de cualquier municipio de España de forma rápida y sencilla.',
            'anio_actual' => date('Y')
        ]);
    }

    public function getNacionales(): void
    {
        $this->render('public/index-nacionales', [
            'titulo' => 'Calendario de Festivos',
            'descripcion' => 'Consulta los días festivos de cualquier municipio de España de forma rápida y sencilla.',
            'anio_actual' => date('Y')
        ]);
    }
}