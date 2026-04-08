<?php

namespace App\Http\Controllers;

use App\Services\MoodleService;

class DashboardController extends Controller
{
    public function __construct(protected MoodleService $moodle) {}

public function index()
{
    $cursosEaD      = $this->moodle->getCursosEaD();
    $totalCursos    = count($cursosEaD);
    $cursosVisibles = collect($cursosEaD)->where('visible', 1)->values();
    $cursosActivos  = $cursosVisibles->count();

    $resumenCursos = $cursosVisibles->map(function ($curso) {
        $creditos = '—';
        foreach ($curso['customfields'] ?? [] as $field) {
            if ($field['shortname'] === 'creditos') {
                $creditos = $field['value'];
                break;
            }
        }
        return [
            'id'              => $curso['id'],
            'nombre'          => $curso['fullname']  ?? 'Sin nombre',
            'corto'           => $curso['shortname'] ?? '',
            'nombreCategoria' => $curso['categoryname'] ?? '—',
            'Docente'         => $curso['contacts'][0]['fullname'] ?? '—',
            'creditos'        => $creditos,
        ];
    })->toArray();

    return view('dashboard', compact('totalCursos', 'cursosActivos', 'resumenCursos'));
}

public function limpiarCache()
{
    \Illuminate\Support\Facades\Cache::flush();
    return back()->with('success', 'Caché limpiado correctamente.');
}
}