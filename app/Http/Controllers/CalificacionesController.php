<?php

namespace App\Http\Controllers;

use App\Services\MoodleService;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CalificacionesController extends Controller
{
    public function __construct(protected MoodleService $moodle) {}

    public function index()
    {
        $categoryIds = request()->input('categories', []);
        
        $raw = $this->moodle->getReporteCalificaciones($categoryIds);
        
        // Obtener eventos del curso 1507
        $eventosFiltrados = $this->getEventosCurso1507();
        
        // ✅ Procesar auditoría de calificaciones post-academia
        $reporteAuditoria = $this->procesarAuditoriaPostAcademia($raw, $eventosFiltrados);
        
        // Agrupar por curso → tema → actividades
        $cursos = $this->agruparCursosPorTemas($raw);
        
        // Totales para KPIs
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
    
    /**
     * ✅ MÉTODO CORREGIDO: Auditar TODOS los temas con cierre anterior a la academia
     */
    private function procesarAuditoriaPostAcademia($raw, $eventosFiltrados): array
    {
        // 1. Encontrar la última reunión de academia
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
        
        // 2. Calcular 1 semana después (5 días hábiles, sin contar fines de semana)
        $fechaReporte = $this->sumarDiasHabiles($fechaAcademia, 5);
        
        // 3. Agrupar actividades por curso
        $actividadesPorCurso = collect($raw)->groupBy('courseid');
        
        // 4. Obtener todos los eventos de cierre
        $eventosCierre = collect($eventosFiltrados)->filter(function($evento) {
            return preg_match('/T\d+: Cierre T\d+/', $evento['name']);
        });
        
        $reporte = [
            'fecha_academia' => $fechaAcademia->format('d/m/Y H:i'),
            'fecha_reporte' => $fechaReporte->format('d/m/Y'),
            'cursos' => []
        ];
        
        // 5. Procesar cada curso INDIVIDUALMENTE
        foreach ($actividadesPorCurso as $courseId => $actividades) {
            $primerActividad = $actividades->first();
            $nombreCurso = $primerActividad['curso'];
            $profesor = $primerActividad['profesor'];
            $totalTemasCurso = $primerActividad['temas'];
            
            // 🔍 Buscar el último tema que cerró ANTES de la academia
            $temaCorte = 0;
            $fechaCorte = null;
            $eventoCorte = null;
            
            foreach ($eventosCierre as $evento) {
                // Patrón: T5: Cierre T3
                if (preg_match('/T(\d+): Cierre T(\d+)/', $evento['name'], $matches)) {
                    $temasDelEvento = (int)$matches[1];
                    $temaNumeroCierre = (int)$matches[2];
                    
                    // Solo eventos que corresponden a la cantidad de temas de ESTE curso
                    if ($temasDelEvento == $totalTemasCurso) {
                        $fechaEvento = Carbon::createFromTimestamp($evento['timestart']);
                        
                        // ✅ El tema cerró ANTES o IGUAL a la academia
                        if ($fechaEvento->lte($fechaAcademia)) {
                            // Tomar el de mayor número de tema (el último cierre)
                            if ($temaNumeroCierre > $temaCorte) {
                                $temaCorte = $temaNumeroCierre;
                                $fechaCorte = $fechaEvento;
                                $eventoCorte = $evento;
                            }
                        }
                    }
                }
            }
            
            // Si no hay evento de cierre para este curso
            if ($temaCorte == 0) {
                $reporte['cursos'][] = [
                    'courseid' => $courseId,
                    'curso' => $nombreCurso,
                    'profesor' => $profesor,
                    'total_temas' => $totalTemasCurso,
                    'tema_corte' => null,
                    'estado' => 'warning',
                    'mensaje' => "No se encontró evento de cierre T{$totalTemasCurso}: Cierre Tn para este curso",
                    'temas_auditados' => []
                ];
                continue;
            }
            
            // ✅ CORRECCIÓN: Los temas a auditar son TODOS desde T1 hasta TEMA_CORTE (inclusive)
            // Porque todos ellos tienen fecha de cierre anterior a la academia
            $temasAAuditar = range(1, $temaCorte);
            
            // Agrupar actividades por tema
            $actividadesPorTema = $actividades->groupBy('tema_numero');
            
            $temasAuditados = [];
            $todosOk = true;
            $hayErrores = false;
            $hayWarnings = false;
            
            // Auditar cada tema (T1 hasta TEMA_CORTE)
            foreach ($temasAAuditar as $temaNum) {
                $actividadesTema = $actividadesPorTema->get($temaNum, collect());
                
                if ($actividadesTema->isEmpty()) {
                    $temasAuditados[] = [
                        'tema_numero' => $temaNum,
                        'estado' => 'warning',
                        'mensaje' => '⚠️ No hay actividades registradas para este tema',
                        'sin_calificar' => 0,
                        'entregados_tarde' => 0,
                        'calificados' => 0,
                        'total_alumnos' => $primerActividad['total_alumnos'] ?? 0
                    ];
                    $todosOk = false;
                    $hayWarnings = true;
                    continue;
                }
                
                // Verificar calificaciones del tema
                $totalSinCalificar = $actividadesTema->sum('sin_calificar');
                $totalEntregadosTarde = $actividadesTema->sum('entregado_tarde');
                $totalCalificados = $actividadesTema->sum('calificados');
                $totalAlumnos = $actividadesTema->first()['total_alumnos'];
                
                // REGLA DE NEGOCIO: 
                // Los sin_calificar están justificados SOLO si son menores o iguales a los entregados_tarde
                $justificado = ($totalSinCalificar <= $totalEntregadosTarde);
                
                // ✅ Determinar si es el tema de corte o uno anterior
                $esTemaCorte = ($temaNum == $temaCorte);
                
                if ($totalSinCalificar == 0) {
                    // ✅ Todo calificado
                    $temasAuditados[] = [
                        'tema_numero' => $temaNum,
                        'estado' => 'ok',
                        'mensaje' => $esTemaCorte ? '✅ Tema de corte - Completamente calificado' : '✅ Completamente calificado',
                        'sin_calificar' => 0,
                        'entregados_tarde' => $totalEntregadosTarde,
                        'calificados' => $totalCalificados,
                        'total_alumnos' => $totalAlumnos,
                        'es_tema_corte' => $esTemaCorte
                    ];
                } elseif ($justificado) {
                    // ⚠️ Hay sin_calificar pero son todos entregados tarde (válido - derecho del profesor)
                    $temasAuditados[] = [
                        'tema_numero' => $temaNum,
                        'estado' => 'warning',
                        'mensaje' => $esTemaCorte 
                            ? "⚠️ Tema de corte - {$totalSinCalificar} sin calificar (justificado: son entregas tardías)"
                            : "⚠️ {$totalSinCalificar} sin calificar (justificado: son entregas tardías)",
                        'sin_calificar' => $totalSinCalificar,
                        'entregados_tarde' => $totalEntregadosTarde,
                        'calificados' => $totalCalificados,
                        'total_alumnos' => $totalAlumnos,
                        'es_tema_corte' => $esTemaCorte
                    ];
                    $hayWarnings = true;
                    // NOTA: Esto es warning, NO es error porque el profesor tiene derecho a no calificar tardíos
                } else {
                    // ❌ Sin calificar no justificado - ERROR
                    $sinJustificar = $totalSinCalificar - $totalEntregadosTarde;
                    $temasAuditados[] = [
                        'tema_numero' => $temaNum,
                        'estado' => 'error',
                        'mensaje' => $esTemaCorte
                            ? "❌ Tema de corte - {$sinJustificar} estudiante(s) sin calificar que SÍ entregaron a tiempo"
                            : "❌ {$sinJustificar} estudiante(s) sin calificar que SÍ entregaron a tiempo",
                        'sin_calificar' => $totalSinCalificar,
                        'entregados_tarde' => $totalEntregadosTarde,
                        'calificados' => $totalCalificados,
                        'total_alumnos' => $totalAlumnos,
                        'es_tema_corte' => $esTemaCorte
                    ];
                    $todosOk = false;
                    $hayErrores = true;
                }
            }
            
            // Determinar estado general del curso
            if ($hayErrores) {
                $estadoGeneral = 'error';
                $mensajeGeneral = "❌ Tema de corte T{$temaCorte} (cerró el {$fechaCorte->format('d/m/Y')}) - Hay temas SIN CALIFICAR que entregaron a tiempo";
            } elseif ($hayWarnings) {
                $estadoGeneral = 'warning';
                $mensajeGeneral = "⚠️ Tema de corte T{$temaCorte} (cerró el {$fechaCorte->format('d/m/Y')}) - Hay sin calificar pero JUSTIFICADOS (entregas tardías)";
            } else {
                $estadoGeneral = 'ok';
                $mensajeGeneral = "✅ Tema de corte T{$temaCorte} (cerró el {$fechaCorte->format('d/m/Y')}) - TODOS los temas están calificados correctamente";
            }
            
            $reporte['cursos'][] = [
                'courseid' => $courseId,
                'curso' => $nombreCurso,
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
        
        // Ordenar cursos: primero los que tienen error, luego warning, luego ok
        $reporte['cursos'] = collect($reporte['cursos'])->sortBy(function($curso) {
            if ($curso['estado'] == 'error') return 0;
            if ($curso['estado'] == 'warning') return 1;
            if ($curso['estado'] == 'info') return 2;
            return 3;
        })->values()->toArray();
        
        return $reporte;
    }
    
    /**
     * Sumar días hábiles a una fecha (lunes a viernes)
     */
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
    
    /**
     * Agrupar cursos por temas
     */
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
                        
                        $aprobados = $totalCalificados;
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
    
    /**
     * Obtener eventos del curso 1507 filtrados por "Cierre" y "Reunión de Academia"
     */
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
}