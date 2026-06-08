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

      public function exportPdfBarChart()
    {
        $categoryIds = request()->input('categories', []);
        
        // Obtener reporte SIN normalizar (para mantener valores reales >100%)
        $reporte = $this->moodle->getReporteAccesosDocentes($categoryIds);
        
        // Procesar manteniendo porcentajes REALES (sin cap de 100%)
        $cursos = collect($reporte)
            ->map(function ($curso) {
                $curso['porcentaje_accesos'] = (float) $curso['porcentaje_accesos'];
                return $curso;
            })
            ->sortBy('porcentaje_accesos')
            ->values()
            ->toArray();
        
        // Filtrar solo cursos con créditos válidos
        $cursosValidos = collect($cursos)->where('creditos', '>', 0)->values();
        
        // Definir los rangos de porcentaje
        $rangos = [
            'menos_25' => [
                'label' => '< 25%',
                'cursos' => [],
                'count' => 0,
                'color' => '#dc2626',  // Rojo intenso
                'icon' => '🔴'
            ],
            'entre_25_50' => [
                'label' => '25% - 50%',
                'cursos' => [],
                'count' => 0,
                'color' => '#f59e0b',  // Naranja
                'icon' => '🟠'
            ],
            'entre_50_75' => [
                'label' => '50% - 75%',
                'cursos' => [],
                'count' => 0,
                'color' => '#eab308',  // Amarillo
                'icon' => '🟡'
            ],
            'entre_75_100' => [
                'label' => '75% - 100%',
                'cursos' => [],
                'count' => 0,
                'color' => '#10b981',  // Verde
                'icon' => '🟢'
            ],
            'mas_100' => [
                'label' => '> 100%',
                'cursos' => [],
                'count' => 0,
                'color' => '#3b82f6',  // Azul
                'icon' => '🔵'
            ]
        ];
        
        // Clasificar cada curso en su rango correspondiente
        foreach ($cursosValidos as $curso) {
            $pct = (float) $curso['porcentaje_accesos'];
            
            if ($pct < 25) {
                $rangos['menos_25']['cursos'][] = $curso;
                $rangos['menos_25']['count']++;
            } elseif ($pct >= 25 && $pct < 50) {
                $rangos['entre_25_50']['cursos'][] = $curso;
                $rangos['entre_25_50']['count']++;
            } elseif ($pct >= 50 && $pct < 75) {
                $rangos['entre_50_75']['cursos'][] = $curso;
                $rangos['entre_50_75']['count']++;
            } elseif ($pct >= 75 && $pct <= 100) {
                $rangos['entre_75_100']['cursos'][] = $curso;
                $rangos['entre_75_100']['count']++;
            } elseif ($pct > 100) {
                $rangos['mas_100']['cursos'][] = $curso;
                $rangos['mas_100']['count']++;
            }
        }
        
        // Estadísticas totales
        $totalCursos = $cursosValidos->count();
        
        // Generar el PDF con la vista de la gráfica
        $pdf = Pdf::loadView('docentes_pdf_barchart', [
            'rangos' => $rangos,
            'totalCursos' => $totalCursos,
        ])->setPaper('a4', 'landscape');
        
        return $pdf->download('reporte-grafico-' . now()->format('Y-m-d') . '.pdf');
    }
}