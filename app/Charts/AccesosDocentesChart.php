<?php

namespace App\Charts;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class AccesosDocentesChart extends Chart
{
    /**
     * Initializes the chart.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Crear gráfica de barras para rangos de acceso
     */
    public static function createBarChart($rangos, $totalCursos)
    {
        $chart = new AccesosDocentesChart();
        
        $labels = [];
        $data = [];
        $backgroundColors = [];
        $borderColors = [];
        
        foreach ($rangos as $rango) {
            $labels[] = $rango['label'];
            $data[] = $rango['count'];
            $backgroundColors[] = $rango['color'] . '80'; // 50% opacity
            $borderColors[] = $rango['color'];
        }
        
        $chart->labels($labels);
        $chart->dataset('Cantidad de Cursos', 'bar', $data)
            ->options([
                'backgroundColor' => $backgroundColors,
                'borderColor' => $borderColors,
                'borderWidth' => 2,
                'borderRadius' => 8,
                'barPercentage' => 0.7,
                'categoryPercentage' => 0.8,
            ]);
        
        // Opciones simplificadas - SIN funciones JavaScript
        $chart->options([
            'responsive' => true,
            'maintainAspectRatio' => true,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'callbacks' => [
                        'label' => 'function(context) { return context.parsed.y + " cursos"; }'
                    ]
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Número de Cursos'
                    ],
                    'grid' => [
                        'color' => '#e2e8f0',
                        'drawBorder' => true,
                    ],
                    'ticks' => [
                        'stepSize' => 1,
                        'precision' => 0
                    ]
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Rangos de Acceso'
                    ],
                    'grid' => [
                        'display' => false
                    ]
                ]
            ]
        ]);
        
        return $chart;
    }
}