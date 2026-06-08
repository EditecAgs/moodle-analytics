<?php

namespace App\Http\Controllers;

use App\Services\MoodleService;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CalificacionesController extends Controller
{
    public function __construct(protected MoodleService $moodle) {}

    public function index()
    {
        $categoryIds = request()->input('categories', []);
        
        $rawActividades = $this->moodle->getReporteCalificaciones($categoryIds);
        $rawForos = $this->moodle->getReporteForos($categoryIds);
        $rawExamenes = $this->moodle->getReporteExamenes($categoryIds);
        
        $raw = $this->unificarReportes($rawActividades, $rawForos, $rawExamenes);
        $eventosFiltrados = $this->getEventosCurso1507();
        $reporteAuditoria = $this->procesarAuditoriaPostAcademia($raw, $eventosFiltrados);
        
        $cursos = $this->agruparCursosPorTemas($raw);
        
        $totalCursos = $cursos->count();
        $totalAprobado = $cursos->sum(fn($c) => $c['totales']['aprobados']);
        $totalReprobado = $cursos->sum(fn($c) => $c['totales']['reprobados']);
        $totalSinCalificar = $cursos->sum(fn($c) => $c['totales']['total_sin_calificar']);
        
        return view('calificaciones', compact(
            'cursos', 
            'totalCursos', 
            'totalAprobado', 
            'totalReprobado', 
            'totalSinCalificar', 
            'eventosFiltrados',
            'reporteAuditoria'
        ));
    }
    
    private function unificarReportes(array $actividades, array $foros, array $examenes): array
    {
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
    
    /**
     * Obtener estudiantes que NO entregaron
     */
    private function obtenerNoEntregaron($actividad): int
    {
        return $actividad['no_entregado'] ?? 0;
    }
    
    /**
     * Calcular justificados (entregaron tarde)
     */
    private function calcularJustificados($actividad): int
    {
        switch ($actividad['tipo_modulo']) {
            case 'assign':
                return $actividad['sin_calificar_entregaron_tarde'] ?? 0;
            case 'forum':
                // Los que cumplieron tarde son JUSTIFICADOS
                return $actividad['entregado_tarde'] ?? 0;
            case 'quiz':
                return 0;
            default:
                return 0;
        }
    }
    
    /**
     * ✅ CORREGIDO: Calcular no justificados (entregaron a tiempo pero NO calificados)
     * 
     * La lógica mejorada:
     * - Para ASSIGN: usamos sin_calificar_entregaron_tiempo directamente
     * - Para FORUM: priorizamos calificados a los que entregaron a tiempo
     * - Para QUIZ: calculamos basado en intentos vs calificados
     */
    private function calcularNoJustificados($actividad): int
    {
        switch ($actividad['tipo_modulo']) {
            case 'assign':
                return $actividad['sin_calificar_entregaron_tiempo'] ?? 0;
                
            case 'forum':
                // FÓRMULA CORREGIDA PARA FOROS:
                // Los calificados se asignan PRIORITARIAMENTE a los que entregaron a tiempo
                // Los que entregaron tarde son JUSTIFICADOS automáticamente
                
                $cumplieronATiempo = $actividad['entregado_a_tiempo'] ?? 0;
                $cumplieronTarde = $actividad['entregado_tarde'] ?? 0;
                $calificados = $actividad['calificados'] ?? 0;
                
                // Asumimos que el profesor califica PRIMERO a los que entregaron a tiempo
                // y luego si tiene tiempo, a los que entregaron tarde
                $calificadosDeTiempo = min($cumplieronATiempo, $calificados);
                
                // Los que entregaron a tiempo pero NO están calificados son el problema
                $noJustificados = max(0, $cumplieronATiempo - $calificadosDeTiempo);
                
                return $noJustificados;
                
            case 'quiz':
                $intentaron = $actividad['intentaron'] ?? 0;
                $calificados = $actividad['calificados'] ?? 0;
                return max(0, $intentaron - $calificados);
                
            default:
                return $actividad['sin_calificar'] ?? 0;
        }
    }
    
    /**
     * Procesar auditoría post-academia
     */
    private function procesarAuditoriaPostAcademia($raw, $eventosFiltrados): array
    {
        $reunionesAcademia = collect($eventosFiltrados)->filter(function($evento) {
            return str_contains($evento['name'], 'Reunión') || 
                   str_contains($evento['name'], 'reunión') ||
                   str_contains($evento['name'], 'academia');
        })->sortByDesc('timestart');
        
        if ($reunionesAcademia->isEmpty()) {
            return ['error' => 'No se encontraron reuniones de academia en los eventos'];
        }
        
        $ultimaAcademia = $reunionesAcademia->first();
        $fechaAcademia = Carbon::createFromTimestamp($ultimaAcademia['timestart']);
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
            
            // Buscar tema de corte
            $temaCorte = 0;
            $fechaCorte = null;
            
            foreach ($eventosCierre as $evento) {
                if (preg_match('/T(\d+): Cierre T(\d+)/', $evento['name'], $matches)) {
                    $temasDelEvento = (int)$matches[1];
                    $temaNumeroCierre = (int)$matches[2];
                    
                    if ($temasDelEvento == $totalTemasCurso) {
                        $fechaEvento = Carbon::createFromTimestamp($evento['timestart']);
                        
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
                $reporte['cursos'][] = [
                    'courseid' => $courseId,
                    'curso' => $nombreCurso,
                    'categoryname' => $primerActividad['categoryname'] ?? 'Sin categoría',
                    'profesor' => $profesor,
                    'total_temas' => $totalTemasCurso,
                    'tema_corte' => null,
                    'estado' => 'warning',
                    'mensaje' => "No se encontró evento de cierre para este curso",
                    'temas_auditados' => []
                ];
                continue;
            }
            
            $temasAAuditar = range(1, $temaCorte);
            $actividadesPorTema = $actividades->groupBy('tema_numero');
            
            $temasAuditados = [];
            $hayErrores = false;
            $hayWarnings = false;
            
            foreach ($temasAAuditar as $temaNum) {
                $actividadesTema = $actividadesPorTema->get($temaNum, collect());
                
                if ($actividadesTema->isEmpty()) {
                    $temasAuditados[] = [
                        'tema_numero' => $temaNum,
                        'estado' => 'warning',
                        'mensaje' => '⚠️ No hay actividades registradas para este tema',
                        'sin_calificar' => 0,
                        'no_entregaron' => 0,
                        'justificados' => 0,
                        'no_justificados' => 0,
                        'calificados' => 0,
                        'total_alumnos' => $primerActividad['total_alumnos'] ?? 0
                    ];
                    $hayWarnings = true;
                    continue;
                }
                
                $totalSinCalificar = 0;
                $totalNoEntregaron = 0;
                $totalJustificados = 0;
                $totalNoJustificados = 0;
                $totalCalificados = 0;
                $totalAlumnos = $actividadesTema->first()['total_alumnos'];
                $detalleActividades = [];
                
                foreach ($actividadesTema as $actividad) {
                    $sinCalificar = $actividad['sin_calificar'];
                    $noEntregaron = $this->obtenerNoEntregaron($actividad);
                    $justificados = $this->calcularJustificados($actividad);
                    $noJustificados = $this->calcularNoJustificados($actividad);
                    
                    $totalSinCalificar += $sinCalificar;
                    $totalNoEntregaron += $noEntregaron;
                    $totalJustificados += $justificados;
                    $totalNoJustificados += $noJustificados;
                    $totalCalificados += $actividad['calificados'];
                    
                    $detalleActividades[] = [
                        'nombre' => $actividad['actividad_nombre'],
                        'tipo' => $actividad['tipo_modulo'],
                        'sin_calificar' => $sinCalificar,
                        'no_entregaron' => $noEntregaron,
                        'justificados' => $justificados,
                        'no_justificados' => $noJustificados
                    ];
                }
                
                $esTemaCorte = ($temaNum == $temaCorte);
                
                // Evaluación del estado del tema
                if ($totalSinCalificar == 0) {
                    $temasAuditados[] = [
                        'tema_numero' => $temaNum,
                        'estado' => 'ok',
                        'mensaje' => $esTemaCorte ? '✅ Tema de corte - Completamente calificado' : '✅ Completamente calificado',
                        'sin_calificar' => 0,
                        'no_entregaron' => 0,
                        'justificados' => 0,
                        'no_justificados' => 0,
                        'calificados' => $totalCalificados,
                        'total_alumnos' => $totalAlumnos,
                        'detalle' => $detalleActividades
                    ];
                } elseif ($totalNoJustificados == 0) {
                    // Solo hay justificados (entregas tardías) o no entregaron
                    $mensaje = $esTemaCorte 
                        ? ""
                        : "";
                    
                    if ($totalNoEntregaron > 0) {
                        $mensaje .= "";
                    }
                    
                    $temasAuditados[] = [
                        'tema_numero' => $temaNum,
                        'estado' => 'warning',
                        'mensaje' => $mensaje,
                        'sin_calificar' => $totalSinCalificar,
                        'no_entregaron' => $totalNoEntregaron,
                        'justificados' => $totalJustificados,
                        'no_justificados' => 0,
                        'calificados' => $totalCalificados,
                        'total_alumnos' => $totalAlumnos,
                        'detalle' => $detalleActividades
                    ];
                    $hayWarnings = true;
                } else {
                    // PROBLEMA REAL: estudiantes que entregaron a tiempo y no están calificados
                    $temasAuditados[] = [
                        'tema_numero' => $temaNum,
                        'estado' => 'error',
                        'mensaje' => $esTemaCorte
                            ? "❌ TEMA DE CORTE - {$totalNoJustificados} entrega(s) A TIEMPO y NO están calificados"
                            : "❌ {$totalNoJustificados} entrega(s) A TIEMPO y NO están calificados",
                        'sin_calificar' => $totalSinCalificar,
                        'no_entregaron' => $totalNoEntregaron,
                        'justificados' => $totalJustificados,
                        'no_justificados' => $totalNoJustificados,
                        'calificados' => $totalCalificados,
                        'total_alumnos' => $totalAlumnos,
                        'detalle' => $detalleActividades
                    ];
                    $hayErrores = true;
                }
            }
            
            // Determinar estado general del curso
            if ($hayErrores) {
                $estadoGeneral = 'error';
                $mensajeGeneral = "❌ Tema de corte T{$temaCorte} - Hay estudiantes que entregaron A TIEMPO y NO están calificados";
            } elseif ($hayWarnings) {
                $estadoGeneral = 'warning';
                $mensajeGeneral = "";
            } else {
                $estadoGeneral = 'ok';
                $mensajeGeneral = "";
            }
            
            $reporte['cursos'][] = [
                'courseid' => $courseId,
                'curso' => $nombreCurso,
                'categoryname' => $primerActividad['categoryname'] ?? 'Sin categoría',
                'profesor' => $profesor,
                'total_temas' => $totalTemasCurso,
                'tema_corte' => $temaCorte,
                'fecha_cierre' => $fechaCorte ? $fechaCorte->format('d/m/Y') : null,
                'temas_requeridos' => $temasAAuditar,
                'temas_auditados' => $temasAuditados,
                'estado' => $estadoGeneral,
                'mensaje' => $mensajeGeneral,
                'hay_errores' => $hayErrores,
                'hay_warnings' => $hayWarnings
            ];
        }
        
        $reporte['cursos'] = collect($reporte['cursos'])->sortBy(function($curso) {
            if ($curso['estado'] == 'error') return 0;
            if ($curso['estado'] == 'warning') return 1;
            return 2;
        })->values()->toArray();
        
        return $reporte;
    }
    
    private function sumarDiasHabiles(Carbon $fecha, int $dias): Carbon
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
    
    private function agruparCursosPorTemas($raw): Collection
    {
        return collect($raw)
            ->groupBy('courseid')
            ->map(function ($actividades, $courseId) {
                $primera = $actividades->first();
                $categoryName = $actividades->first()['categoryname'] ?? 'Sin categoría';
                
                $temas = $actividades
                    ->groupBy('tema_numero')
                    ->map(function ($actividadesTema, $temaNumero) {
                        $temaInfo = $actividadesTema->first();
                        $totalAlumnos = $actividadesTema->first()['total_alumnos'];
                        
                        $totalCalificados = $actividadesTema->sum('calificados');
                        $totalSinCalificar = $actividadesTema->sum('sin_calificar');
                        $totalReabiertos = $actividadesTema->sum('reopened');
                        $totalEntregados = $actividadesTema->sum('entregado_a_tiempo') + $actividadesTema->sum('entregado_tarde');
                        $totalNoEntregados = $actividadesTema->sum('no_entregado');
                        
                        $aprobados = $actividadesTema->sum('aprobados');
                        $reprobados = 0;
                        
                        return [
                            'tema_numero' => $temaNumero,
                            'tema' => $temaInfo['tema'],
                            'total_alumnos' => $totalAlumnos,
                            'total_calificados' => $totalCalificados,
                            'total_sin_calificar' => $totalSinCalificar,
                            'total_reabiertos' => $totalReabiertos,
                            'total_entregados' => $totalEntregados,
                            'total_no_entregados' => $totalNoEntregados,
                            'aprobados' => $aprobados,
                            'reprobados' => $reprobados,
                            'actividades' => $actividadesTema->map(fn($a) => [
                                'actividad_id' => $a['actividad_id'],
                                'actividad_nombre' => $a['actividad_nombre'],
                                'tipo_modulo' => $a['tipo_modulo'],
                                'total_alumnos' => $a['total_alumnos'],
                                'no_entregado' => $a['no_entregado'],
                                'entregado_a_tiempo' => $a['entregado_a_tiempo'],
                                'entregado_tarde' => $a['entregado_tarde'],
                                'reopened' => $a['reopened'],
                                'calificados' => $a['calificados'],
                                'sin_calificar' => $a['sin_calificar'],
                                'grade_max' => $a['grade_max'] ?? 100,
                                'fecha_apertura' => $a['fecha_apertura'],
                                'fecha_limite' => $a['fecha_limite'],
                                'criterio_finalizacion' => $a['criterio_finalizacion'] ?? null,
                                'alumnos_con_disc' => $a['alumnos_con_disc'] ?? null,
                                'alumnos_con_rep' => $a['alumnos_con_rep'] ?? null,
                                'intentaron' => $a['intentaron'] ?? null,
                            ])->values()->toArray(),
                        ];
                    })->values()->toArray();
                
                $totalesCurso = [
                    'total_alumnos' => $primera['total_alumnos'],
                    'total_calificados' => collect($temas)->sum('total_calificados'),
                    'total_sin_calificar' => collect($temas)->sum('total_sin_calificar'),
                    'total_reabiertos' => collect($temas)->sum('total_reabiertos'),
                    'total_entregados' => collect($temas)->sum('total_entregados'),
                    'total_no_entregados' => collect($temas)->sum('total_no_entregados'),
                    'aprobados' => collect($temas)->sum('aprobados'),
                    'reprobados' => collect($temas)->sum('reprobados'),
                ];
                
                return [
                    'courseid' => $courseId,
                    'curso' => $primera['curso'],
                    'categoryname' => $categoryName,
                    'profesor' => $primera['profesor'],
                    'total_alumnos' => $primera['total_alumnos'],
                    'temas' => $temas,
                    'totales' => $totalesCurso,
                ];
            })
            ->values();
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

    public function descargarPDF()
    {
        $categoryIds = request()->input('categories', []);
        
        $rawActividades = $this->moodle->getReporteCalificaciones($categoryIds);
        $rawForos = $this->moodle->getReporteForos($categoryIds);
        $rawExamenes = $this->moodle->getReporteExamenes($categoryIds);
        $raw = $this->unificarReportes($rawActividades, $rawForos, $rawExamenes);
        $eventosFiltrados = $this->getEventosCurso1507();
        $reporteAuditoria = $this->procesarAuditoriaPostAcademia($raw, $eventosFiltrados);
        
        $datosPDF = $this->prepararDatosParaPDF($reporteAuditoria);
        
        $pdf = PDF::loadView('pdf.auditoria_calificaciones', $datosPDF);
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download('auditoria_calificaciones_' . now()->format('Ymd_His') . '.pdf');
    }
    
private function prepararDatosParaPDF(array $reporteAuditoria): array
{
    $cursosReporte = [];

    $cursosConError = collect($reporteAuditoria['cursos'])
        ->where('estado', 'error');

    foreach ($cursosConError as $curso) {

        $seguimiento = [];
        $observaciones = [];

        if (isset($curso['temas_auditados']) && !empty($curso['temas_auditados'])) {

            foreach ($curso['temas_auditados'] as $tema) {

                $temaNum = $tema['tema_numero'];

                if ($tema['estado'] == 'error') {
                    $seguimiento[] = "Falta T{$temaNum}";
                    $observaciones[] = "T{$temaNum}: " . $tema['mensaje'];
                }
            }
        }

        $cursosReporte[] = [
            'curso' => $curso['curso'],
            'categoria' => $curso['categoryname'] ?? 'Sin categoría',
            'profesor' => $curso['profesor'],
            'tema_corte' => $curso['tema_corte'] ?? 'N/A',
            'seguimiento' => implode(' · ', $seguimiento),
            'observaciones' => !empty($observaciones)
                ? implode(' | ', $observaciones)
                : 'Sin observaciones',
            'estado' => $curso['estado'],
            'mensaje' => $curso['mensaje']
        ];
    }

    return [
        'cursos' => $cursosReporte,
        'fecha_academia' => $reporteAuditoria['fecha_academia'],
        'fecha_reporte' => $reporteAuditoria['fecha_reporte'],
        'total_cursos' => count($cursosReporte),
        'cursos_error' => count($cursosReporte),
        'cursos_warning' => 0,
        'cursos_ok' => 0
    ];
}
    /**
 * Exportar PDF con gráfica de pastel de estados de calificación por curso
 * Basado en los temas que YA DEBÍAN estar calificados (hasta el tema de corte)
 */
public function descargarPDFGrafica()
{
    $categoryIds = request()->input('categories', []);
    
    // Obtener los mismos datos que en index()
    $rawActividades = $this->moodle->getReporteCalificaciones($categoryIds);
    $rawForos = $this->moodle->getReporteForos($categoryIds);
    $rawExamenes = $this->moodle->getReporteExamenes($categoryIds);
    
    $raw = $this->unificarReportes($rawActividades, $rawForos, $rawExamenes);
    $eventosFiltrados = $this->getEventosCurso1507();
    
    // Generar el reporte de auditoría (que ya tiene la lógica de temas que debían estar calificados)
    $reporteAuditoria = $this->procesarAuditoriaPostAcademia($raw, $eventosFiltrados);
    
    // Analizar estado de calificación por curso usando el reporte de auditoría
    $estadosCursos = $this->analizarEstadosDesdeAuditoria($reporteAuditoria);
    
    // Preparar datos para la gráfica de pastel
    $datosGrafica = [
        'labels' => [],
        'data' => [],
        'colors' => [],
        'total_cursos' => $estadosCursos['total']
    ];
    
    foreach ($estadosCursos['detalle'] as $key => $estado) {
        if ($estado['count'] > 0) {
            $datosGrafica['labels'][] = $estado['label'];
            $datosGrafica['data'][] = $estado['count'];
            $datosGrafica['colors'][] = $estado['color'];
        }
    }
    
    $pdf = PDF::loadView('pdf.grafica_calificaciones_cursos', [
        'datosGrafica' => $datosGrafica,
        'estadosCursos' => $estadosCursos,
        'reporteAuditoria' => $reporteAuditoria,
        'fechaGeneracion' => now()->format('d/m/Y H:i:s')
    ]);
    $pdf->setPaper('a4', 'landscape');
    
    return $pdf->download('reporte_estados_calificacion_' . now()->format('Ymd_His') . '.pdf');
}

/**
 * Analizar el estado de calificación de cada curso
 * Basado en el reporte de auditoría (temas que debían estar calificados hasta el corte)
 * 
 * Estados:
 * - CALIFICADO COMPLETO: Todos los temas hasta el corte están calificados
 * - 1 UNIDAD POR CALIFICAR: Exactamente 1 tema sin calificar (que debía estar calificado)
 * - MÚLTIPLES UNIDADES POR CALIFICAR: Más de 1 tema sin calificar (que debían estar calificados)
 * - NO CALIFICADO: Ningún tema calificado (de los que debían estar)
 */
/**
 * Analizar el estado de calificación de cada curso
 * Basado en el reporte de auditoría (temas que debían estar calificados hasta el corte)
 * 
 * NOTA: Los cursos con estado 'warning' (entregas tardías / justificados) se consideran
 * como "Completamente calificado" porque no hay estudiantes que entregaron a tiempo sin calificar.
 */
private function analizarEstadosDesdeAuditoria($reporteAuditoria): array
{
    $estados = [
        'calificado_completo' => [
            'label' => '✅ Completamente calificado (incluye justificados)',
            'count' => 0,
            'color' => '#10b981',
            'cursos' => []
        ],
        'una_unidad' => [
            'label' => '📌 1 unidad por calificar (debió estarlo)',
            'count' => 0,
            'color' => '#3b82f6',
            'cursos' => []
        ],
        'multiples_unidades' => [
            'label' => '⚠️ Múltiples unidades por calificar (>1)',
            'count' => 0,
            'color' => '#f59e0b',
            'cursos' => []
        ],
        'no_calificado' => [
            'label' => '❌ No calificado (0 unidades de las debidas)',
            'count' => 0,
            'color' => '#ef4444',
            'cursos' => []
        ]
    ];
    
    foreach ($reporteAuditoria['cursos'] as $cursoAuditado) {
        // Saltar cursos sin tema de corte
        if (!isset($cursoAuditado['tema_corte']) || $cursoAuditado['tema_corte'] === null) {
            continue;
        }
        
        $temasAuditados = $cursoAuditado['temas_auditados'] ?? [];
        $totalTemasDebidos = count($temasAuditados);
        
        // Contar temas con ERROR REAL (entregaron a tiempo sin calificar)
        // Los temas con estado 'warning' (justificados) NO cuentan como error
        $temasConError = 0;
        $temasCalificadosOk = 0;
        
        foreach ($temasAuditados as $tema) {
            // Solo error si es 'error' Y tiene no_justificados > 0
            if ($tema['estado'] == 'error' && ($tema['no_justificados'] ?? 0) > 0) {
                $temasConError++;
            } else {
                $temasCalificadosOk++;
            }
        }
        
        $infoCurso = [
            'courseid' => $cursoAuditado['courseid'],
            'curso' => $cursoAuditado['curso'],
            'categoryname' => $cursoAuditado['categoryname'],
            'profesor' => $cursoAuditado['profesor'],
            'tema_corte' => $cursoAuditado['tema_corte'],
            'total_temas_debidos' => $totalTemasDebidos,
            'temas_calificados_ok' => $temasCalificadosOk,
            'temas_con_problema' => $temasConError,
            'detalle_temas' => $temasAuditados,
            'estado_original' => $cursoAuditado['estado'] // guardamos para debug
        ];
        
        // Clasificar según el estado (ahora los warning van a calificado_completo)
        if ($temasConError == 0) {
            // No hay errores reales -> todos los temas están OK (incluye warning)
            $estados['calificado_completo']['count']++;
            $estados['calificado_completo']['cursos'][] = $infoCurso;
        } elseif ($temasConError == $totalTemasDebidos) {
            // Todos los temas tienen error
            $estados['no_calificado']['count']++;
            $estados['no_calificado']['cursos'][] = $infoCurso;
        } elseif ($temasConError == 1) {
            // Exactamente 1 tema con error
            $estados['una_unidad']['count']++;
            $estados['una_unidad']['cursos'][] = $infoCurso;
        } else {
            // Más de 1 tema con error
            $estados['multiples_unidades']['count']++;
            $estados['multiples_unidades']['cursos'][] = $infoCurso;
        }
    }
    
    return [
        'total' => $reporteAuditoria['total_cursos'] ?? count($reporteAuditoria['cursos']),
        'fecha_academia' => $reporteAuditoria['fecha_academia'],
        'fecha_reporte' => $reporteAuditoria['fecha_reporte'],
        'detalle' => $estados,
        'resumen' => [
            'calificados' => $estados['calificado_completo']['count'],
            'por_calificar' => $estados['multiples_unidades']['count'] + $estados['una_unidad']['count'],
            'criticos' => $estados['no_calificado']['count']
        ]
    ];
}

}