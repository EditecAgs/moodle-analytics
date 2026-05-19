<?php

namespace App\Http\Controllers;

use App\Services\MoodleService;
use Barryvdh\DomPDF\Facade\Pdf;

class DocentesController extends Controller
{
    public function __construct(protected MoodleService $moodle) {}

    public function index()
    {
        $categoryIds = request()->input('categories', []);
        $cursos = $this->getCursosProcesados($categoryIds);
        return view('docentes', compact('cursos'));
    }
    public function exportPdfAll()
{
    $categoryIds = request()->input('categories', []);
    $cursos = $this->getCursosProcesados($categoryIds);

    // Solo cursos con créditos válidos, ya vienen ordenados por porcentaje ASC
    // Reagrupamos: rojos → amarillos → verdes
    $todos = collect($cursos)->where('creditos', '>', 0);

    $rojos     = $todos->where('porcentaje_accesos', '<=', 60)->values();
    $amarillos = $todos->where('porcentaje_accesos', '>', 60)->where('porcentaje_accesos', '<=', 80)->values();
    $verdes    = $todos->where('porcentaje_accesos', '>', 80)->values();

    $ordenados = $rojos->concat($amarillos)->concat($verdes)->toArray();

    $pdf = Pdf::loadView('docentes_pdf_all', ['cursos' => $ordenados])
        ->setPaper('a4', 'landscape');

    return $pdf->download('reporte-completo-' . now()->format('Y-m-d') . '.pdf');
}

    public function exportPdf()
    {
        $categoryIds = request()->input('categories', []);
        $cursos = $this->getCursosProcesados($categoryIds);

        // Solo críticos para el PDF
        $criticos = collect($cursos)
            ->where('creditos', '>', 0)
            ->where('porcentaje_accesos', '<=', 60)
            ->values()
            ->toArray();

        $pdf = Pdf::loadView('docentes_pdf', ['cursos' => $criticos])
            ->setPaper('a4', 'landscape');

        return $pdf->download('reporte-criticos-' . now()->format('Y-m-d') . '.pdf');
    }

    private function getCursosProcesados(array $categoryIds): array
    {
        $reporte = $this->moodle->getReporteAccesosDocentes($categoryIds);

        return collect($reporte)
            ->map(function ($curso) {
                // Cap en 100%
                $curso['porcentaje_accesos'] = min((float) $curso['porcentaje_accesos'], 100);
                return $curso;
            })
            ->sortBy('porcentaje_accesos')
            ->values()
            ->toArray();
    }
}