<?php

namespace App\Http\Controllers;

use App\Services\MoodleService;
use Illuminate\Http\Request;

class ChartImageController extends Controller
{
    protected $moodle;

    public function __construct(MoodleService $moodle)
    {
        $this->moodle = $moodle;
    }

    public function generarGraficaPastel(Request $request)
    {
        $categoryIds = $request->input('categories', []);
        
        // Obtener los datos igual que en CalificacionesController
        $rawActividades = $this->moodle->getReporteCalificaciones($categoryIds);
        $rawForos = $this->moodle->getReporteForos($categoryIds);
        $rawExamenes = $this->moodle->getReporteExamenes($categoryIds);
        
        $raw = $this->unificarReportes($rawActividades, $rawForos, $rawExamenes);
        $eventosFiltrados = $this->getEventosCurso1507();
        $reporteAuditoria = $this->procesarAuditoriaPostAcademia($raw, $eventosFiltrados);
        
        // Analizar estados
        $estadosCursos = $this->analizarEstadosDesdeAuditoria($reporteAuditoria);
        
        // Preparar datos para la gráfica
        $datos = [];
        $labels = [];
        $colores = [];
        
        $detalle = $estadosCursos['detalle'];
        
        if ($detalle['calificado_completo']['count'] > 0) {
            $datos[] = $detalle['calificado_completo']['count'];
            $labels[] = 'Completamente calificado';
            $colores[] = [16, 150, 67]; // #10b981
        }
        if ($detalle['una_unidad']['count'] > 0) {
            $datos[] = $detalle['una_unidad']['count'];
            $labels[] = '1 unidad por calificar';
            $colores[] = [37, 99, 235]; // #3b82f6
        }
        if ($detalle['multiples_unidades']['count'] > 0) {
            $datos[] = $detalle['multiples_unidades']['count'];
            $labels[] = 'Múltiples unidades (>1)';
            $colores[] = [217, 119, 6]; // #f59e0b
        }
        if ($detalle['no_calificado']['count'] > 0) {
            $datos[] = $detalle['no_calificado']['count'];
            $labels[] = 'No calificado';
            $colores[] = [220, 38, 38]; // #ef4444
        }
        
        $total = array_sum($datos);
        
        // Crear imagen
        $width = 600;
        $height = 500;
        $image = imagecreatetruecolor($width, $height);
        
        // Colores
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 100, 116, 139);
        $darkGray = imagecolorallocate($image, 30, 41, 59);
        
        // Fondo blanco
        imagefill($image, 0, 0, $white);
        
        // Dibujar borde
        imagerectangle($image, 0, 0, $width - 1, $height - 1, $darkGray);
        
        // Título
        $title = "DISTRIBUCIÓN POR ESTADO DE CALIFICACIÓN";
        $fontSize = 5; // 5 = 14px aprox
        $titleWidth = imagefontwidth($fontSize) * strlen($title);
        $titleX = ($width - $titleWidth) / 2;
        imagestring($image, $fontSize, $titleX, 20, $title, $darkGray);
        
        // Dibujar gráfica de pastel
        $cx = $width / 2 - 50; // centro X
        $cy = $height / 2 + 20; // centro Y
        $radius = 150;
        
        $startAngle = 0;
        
        foreach ($datos as $i => $value) {
            $percentage = ($value / $total) * 100;
            $angle = ($value / $total) * 360;
            $endAngle = $startAngle + $angle;
            
            $color = imagecolorallocate($image, $colores[$i][0], $colores[$i][1], $colores[$i][2]);
            
            // Dibujar sector
            imagefilledarc($image, $cx, $cy, $radius * 2, $radius * 2, $startAngle, $endAngle, $color, IMG_ARC_PIE);
            
            // Calcular posición para etiqueta
            $midAngle = deg2rad($startAngle + ($angle / 2));
            $labelX = $cx + ($radius * 0.7) * cos($midAngle);
            $labelY = $cy + ($radius * 0.7) * sin($midAngle);
            
            // Mostrar porcentaje si el sector es grande
            if ($angle > 15) {
                $pctText = round($percentage, 1) . '%';
                $textWidth = imagefontwidth(3) * strlen($pctText);
                $textHeight = imagefontheight(3);
                imagestring($image, 3, $labelX - $textWidth / 2, $labelY - $textHeight / 2, $pctText, $white);
            }
            
            $startAngle = $endAngle;
        }
        
        // Dibujar círculo interior (efecto donut)
        $innerRadius = 70;
        imagefilledarc($image, $cx, $cy, $innerRadius * 2, $innerRadius * 2, 0, 360, $white, IMG_ARC_PIE);
        
        // Texto central
        $totalText = $total;
        $totalFontSize = 5;
        $totalWidth = imagefontwidth($totalFontSize) * strlen($totalText);
        imagestring($image, $totalFontSize, $cx - $totalWidth / 2, $cy - 10, $totalText, $darkGray);
        imagestring($image, 2, $cx - 20, $cy + 5, "cursos", $gray);
        
        // Leyenda
        $legendX = $width - 140;
        $legendY = 80;
        
        for ($i = 0; $i < count($datos); $i++) {
            $color = imagecolorallocate($image, $colores[$i][0], $colores[$i][1], $colores[$i][2]);
            $yPos = $legendY + ($i * 25);
            
            // Cuadro de color
            imagefilledrectangle($image, $legendX, $yPos, $legendX + 12, $yPos + 12, $color);
            imagerectangle($image, $legendX, $yPos, $legendX + 12, $yPos + 12, $darkGray);
            
            // Texto de leyenda
            $legendText = $labels[$i] . " (" . $datos[$i] . ")";
            imagestring($image, 2, $legendX + 18, $yPos + 2, $legendText, $darkGray);
        }
        
        // Footer
        $footer = "TecNM Campus Aguascalientes";
        $footerWidth = imagefontwidth(2) * strlen($footer);
        imagestring($image, 2, ($width - $footerWidth) / 2, $height - 20, $footer, $gray);
        
        // Enviar imagen como respuesta
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);
        
        return response($imageData)->header('Content-Type', 'image/png');
    }
    
    // Copia los métodos auxiliares del CalificacionesController
    private function unificarReportes(array $actividades, array $foros, array $examenes): array
    {
        // Copiar el método de tu CalificacionesController
        $unificados = [];
        
        foreach ($actividades as $item) {
            $unificados[] = $this->normalizarActividad($item, 'assign');
        }
        
        foreach ($foros as $item) {
            $unificados[] = $this->normalizarForo($item);
        }
        
        foreach ($examenes as $item) {
            $unificados[] = $this->normalizarExamen($item);
        }
        
        return $unificados;
    }
    
    private function normalizarActividad(array $item, string $tipo): array
    {
        return [
            'courseid' => $item['courseid'],
            'curso' => $item['curso'],
            'categoryname' => $item['categoryname'],
            'temas' => $item['temas'],
            'profesor' => $item['profesor'],
            'tema_numero' => $item['tema_numero'],
            'tema' => $item['tema'],
            'actividad_id' => $item['actividad_id'],
            'actividad_nombre' => $item['actividad_nombre'],
            'tipo_modulo' => $tipo,
            'grade_max' => $item['grade_max'] ?? 100,
            'grade_pass' => $item['grade_pass'] ?? 70,
            'fecha_apertura' => $item['fecha_apertura'] ?? null,
            'fecha_limite' => $item['fecha_limite'] ?? null,
            'total_alumnos' => $item['total_alumnos'],
            'no_entregado' => $item['no_entregado'] ?? 0,
            'entregado_a_tiempo' => $item['entregado_a_tiempo'] ?? 0,
            'entregado_tarde' => $item['entregado_tarde'] ?? 0,
            'reopened' => $item['reopened'] ?? 0,
            'calificados' => $item['calificados'] ?? 0,
            'sin_calificar' => $item['sin_calificar_total'] ?? $item['sin_calificar'] ?? 0,
            'aprobados' => $item['aprobados'] ?? 0,
            'sin_calificar_no_entregaron' => $item['sin_calificar_no_entregaron'] ?? 0,
            'sin_calificar_entregaron_tiempo' => $item['sin_calificar_entregaron_tiempo'] ?? 0,
            'sin_calificar_entregaron_tarde' => $item['sin_calificar_entregaron_tarde'] ?? 0,
        ];
    }
    
    private function normalizarForo(array $item): array
    {
        return [
            'courseid' => $item['courseid'],
            'curso' => $item['curso'],
            'categoryname' => $item['categoryname'],
            'temas' => $item['temas'],
            'profesor' => $item['profesor'],
            'tema_numero' => $item['tema_numero'],
            'tema' => $item['tema'],
            'actividad_id' => $item['actividad_id'],
            'actividad_nombre' => $item['actividad_nombre'],
            'tipo_modulo' => 'forum',
            'grade_max' => $item['grade_max'] ?? 100,
            'grade_pass' => $item['grade_pass'] ?? 70,
            'fecha_apertura' => null,
            'fecha_limite' => $item['fecha_limite'] ?? null,
            'total_alumnos' => $item['total_alumnos'],
            'no_entregado' => $item['no_cumplieron'] ?? 0,
            'entregado_a_tiempo' => $item['cumplieron_a_tiempo'] ?? 0,
            'entregado_tarde' => $item['cumplieron_tarde'] ?? 0,
            'reopened' => 0,
            'calificados' => $item['calificados'] ?? 0,
            'sin_calificar' => $item['sin_calificar'] ?? 0,
            'aprobados' => $item['aprobados'] ?? 0,
            'cumplieron_actividad' => $item['cumplieron_actividad'] ?? 0,
            'alumnos_con_disc' => $item['alumnos_con_disc'] ?? 0,
            'alumnos_con_rep' => $item['alumnos_con_rep'] ?? 0,
            'criterio_finalizacion' => $item['criterio_finalizacion'] ?? '',
            'cumplieron_a_tiempo' => $item['cumplieron_a_tiempo'] ?? 0,
            'cumplieron_tarde' => $item['cumplieron_tarde'] ?? 0,
            'sin_calificar_entregaron_tiempo' => 0,
            'sin_calificar_entregaron_tarde' => 0,
        ];
    }
    
    private function normalizarExamen(array $item): array
    {
        $intentaron = $item['intentaron'] ?? 0;
        $calificados = $item['calificados'] ?? 0;
        $sinCalificar = $item['sin_calificar'] ?? 0;
        
        return [
            'courseid' => $item['courseid'],
            'curso' => $item['curso'],
            'categoryname' => $item['categoryname'],
            'temas' => $item['temas'],
            'profesor' => $item['profesor'],
            'tema_numero' => $item['tema_numero'],
            'tema' => $item['tema'],
            'actividad_id' => $item['actividad_id'],
            'actividad_nombre' => $item['actividad_nombre'],
            'tipo_modulo' => 'quiz',
            'grade_max' => $item['grade_max'] ?? 100,
            'grade_pass' => $item['grade_pass'] ?? 70,
            'fecha_apertura' => $item['fecha_apertura'] ?? null,
            'fecha_limite' => $item['fecha_limite'] ?? null,
            'total_alumnos' => $item['total_alumnos'],
            'no_entregado' => max(0, $item['total_alumnos'] - $intentaron),
            'entregado_a_tiempo' => $intentaron,
            'entregado_tarde' => 0,
            'reopened' => 0,
            'calificados' => $calificados,
            'sin_calificar' => $sinCalificar,
            'aprobados' => $item['aprobados'] ?? 0,
            'intentaron' => $intentaron,
            'sin_calificar_entregaron_tiempo' => max(0, $intentaron - $calificados),
            'sin_calificar_entregaron_tarde' => 0,
        ];
    }
    
    private function getEventosCurso1507(): array
    {
        $cursoId = 1507;
        $eventos = $this->moodle->getEventos([$cursoId]);
        
        $palabrasClave = ['Cierre', 'Reunión', 'reunión', 'Reunion', 'reunion', 'Academia', 'academia'];
        
        $eventosFiltrados = collect($eventos)->filter(function ($evento) use ($palabrasClave) {
            $nombre = $evento['name'] ?? '';
            foreach ($palabrasClave as $palabra) {
                if (str_contains($nombre, $palabra)) {
                    return true;
                }
            }
            return false;
        })->map(function ($evento) {
            return [
                'id' => $evento['id'],
                'name' => $evento['name'],
                'description' => $evento['description'] ?? '',
                'timestart' => $evento['timestart'],
                'timeend' => $evento['timeend'] ?? $evento['timestart'],
                'eventtype' => $evento['eventtype'] ?? 'general',
                'courseid' => $evento['courseid'],
            ];
        })->sortBy('timestart')
        ->values()
        ->toArray();
        
        return $eventosFiltrados;
    }
    
    private function procesarAuditoriaPostAcademia($raw, $eventosFiltrados): array
    {
        // Copiar este método de tu CalificacionesController
        $reunionesAcademia = collect($eventosFiltrados)->filter(function($evento) {
            return str_contains($evento['name'], 'Reunión') || 
                   str_contains($evento['name'], 'reunión') ||
                   str_contains($evento['name'], 'academia');
        })->sortByDesc('timestart');
        
        if ($reunionesAcademia->isEmpty()) {
            return ['error' => 'No se encontraron reuniones de academia en los eventos'];
        }
        
        $ultimaAcademia = $reunionesAcademia->first();
        $fechaAcademia = \Carbon\Carbon::createFromTimestamp($ultimaAcademia['timestart']);
        $fechaReporte = $this->sumarDiasHabiles($fechaAcademia, 5);
        
        $actividadesPorCurso = collect($raw)->groupBy('courseid');
        
        $eventosCierre = collect($eventosFiltrados)->filter(function($evento) {
            return preg_match('/T\d+: Cierre T\d+/', $evento['name']);
        });
        
        $reporte = [
            'fecha_academia' => $fechaAcademia->format('d/m/Y H:i'),
            'fecha_reporte' => $fechaReporte->format('d/m/Y'),
            'cursos' => []
        ];
        
        foreach ($actividadesPorCurso as $courseId => $actividades) {
            $primerActividad = $actividades->first();
            $nombreCurso = $primerActividad['curso'];
            $profesor = $primerActividad['profesor'];
            $totalTemasCurso = $primerActividad['temas'];
            
            $temaCorte = 0;
            $fechaCorte = null;
            
            foreach ($eventosCierre as $evento) {
                if (preg_match('/T(\d+): Cierre T(\d+)/', $evento['name'], $matches)) {
                    $temasDelEvento = (int)$matches[1];
                    $temaNumeroCierre = (int)$matches[2];
                    
                    if ($temasDelEvento == $totalTemasCurso) {
                        $fechaEvento = \Carbon\Carbon::createFromTimestamp($evento['timestart']);
                        
                        if ($fechaEvento->lte($fechaAcademia)) {
                            if ($temaNumeroCierre > $temaCorte) {
                                $temaCorte = $temaNumeroCierre;
                                $fechaCorte = $fechaEvento;
                            }
                        }
                    }
                }
            }
            
            if ($temaCorte == 0) {
                continue;
            }
            
            $temasAAuditar = range(1, $temaCorte);
            $actividadesPorTema = $actividades->groupBy('tema_numero');
            
            $temasAuditados = [];
            
            foreach ($temasAAuditar as $temaNum) {
                $actividadesTema = $actividadesPorTema->get($temaNum, collect());
                
                if ($actividadesTema->isEmpty()) {
                    $temasAuditados[] = [
                        'tema_numero' => $temaNum,
                        'estado' => 'warning',
                        'mensaje' => 'No hay actividades registradas',
                        'no_justificados' => 0
                    ];
                    continue;
                }
                
                $totalNoJustificados = 0;
                
                foreach ($actividadesTema as $actividad) {
                    $noJustificados = $this->calcularNoJustificados($actividad);
                    $totalNoJustificados += $noJustificados;
                }
                
                $estado = $totalNoJustificados == 0 ? 'ok' : 'error';
                $temasAuditados[] = [
                    'tema_numero' => $temaNum,
                    'estado' => $estado,
                    'no_justificados' => $totalNoJustificados,
                    'mensaje' => $totalNoJustificados > 0 ? "{$totalNoJustificados} estudiante(s) sin calificar" : "Completamente calificado"
                ];
            }
            
            $reporte['cursos'][] = [
                'courseid' => $courseId,
                'curso' => $nombreCurso,
                'categoryname' => $primerActividad['categoryname'] ?? 'Sin categoría',
                'profesor' => $profesor,
                'tema_corte' => $temaCorte,
                'temas_auditados' => $temasAuditados
            ];
        }
        
        return $reporte;
    }
    
    private function analizarEstadosDesdeAuditoria($reporteAuditoria): array
    {
        $estados = [
            'calificado_completo' => ['count' => 0, 'cursos' => []],
            'una_unidad' => ['count' => 0, 'cursos' => []],
            'multiples_unidades' => ['count' => 0, 'cursos' => []],
            'no_calificado' => ['count' => 0, 'cursos' => []]
        ];
        
        foreach ($reporteAuditoria['cursos'] as $cursoAuditado) {
            if (!isset($cursoAuditado['tema_corte'])) {
                continue;
            }
            
            $temasAuditados = $cursoAuditado['temas_auditados'] ?? [];
            $temasConProblema = 0;
            
            foreach ($temasAuditados as $tema) {
                if (($tema['no_justificados'] ?? 0) > 0) {
                    $temasConProblema++;
                }
            }
            
            $totalTemas = count($temasAuditados);
            $temasCalificadosOk = $totalTemas - $temasConProblema;
            
            $infoCurso = [
                'curso' => $cursoAuditado['curso'],
                'categoryname' => $cursoAuditado['categoryname'],
                'profesor' => $cursoAuditado['profesor'],
                'tema_corte' => $cursoAuditado['tema_corte'],
                'total_temas_debidos' => $totalTemas,
                'temas_calificados_ok' => $temasCalificadosOk,
                'temas_con_problema' => $temasConProblema
            ];
            
            if ($temasConProblema == 0) {
                $estados['calificado_completo']['count']++;
                $estados['calificado_completo']['cursos'][] = $infoCurso;
            } elseif ($temasConProblema == $totalTemas) {
                $estados['no_calificado']['count']++;
                $estados['no_calificado']['cursos'][] = $infoCurso;
            } elseif ($temasConProblema == 1) {
                $estados['una_unidad']['count']++;
                $estados['una_unidad']['cursos'][] = $infoCurso;
            } else {
                $estados['multiples_unidades']['count']++;
                $estados['multiples_unidades']['cursos'][] = $infoCurso;
            }
        }
        
        return ['detalle' => $estados];
    }
    
    private function calcularNoJustificados($actividad): int
    {
        switch ($actividad['tipo_modulo']) {
            case 'assign':
                return $actividad['sin_calificar_entregaron_tiempo'] ?? 0;
            case 'forum':
                $cumplieronATiempo = $actividad['entregado_a_tiempo'] ?? 0;
                $calificados = $actividad['calificados'] ?? 0;
                $calificadosDeTiempo = min($cumplieronATiempo, $calificados);
                return max(0, $cumplieronATiempo - $calificadosDeTiempo);
            case 'quiz':
                $intentaron = $actividad['intentaron'] ?? 0;
                $calificados = $actividad['calificados'] ?? 0;
                return max(0, $intentaron - $calificados);
            default:
                return 0;
        }
    }
    
    private function sumarDiasHabiles($fecha, int $dias): \Carbon\Carbon
    {
        $resultado = clone $fecha;
        $diasAgregados = 0;
        
        while ($diasAgregados < $dias) {
            $resultado->addDay();
            if ($resultado->isWeekday()) {
                $diasAgregados++;
            }
        }
        
        return $resultado;
    }
}