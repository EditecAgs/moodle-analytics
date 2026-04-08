<?php

namespace App\Http\Controllers;

use App\Services\MoodleService;

class DocentesController extends Controller
{
    public function __construct(protected MoodleService $moodle) {}

public function index()
{

    $categoryIds = request()->input('categories', []);

    $reporte = $this->moodle->getReporteAccesosDocentes($categoryIds);


    $cursos = collect($reporte)
        ->sortBy('porcentaje_accesos')
        ->values()
        ->toArray();
  
    return view('docentes', compact('cursos'));
}
}