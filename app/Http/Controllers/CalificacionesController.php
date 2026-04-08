<?php

namespace App\Http\Controllers;

use App\Services\MoodleService;

class CalificacionesController extends Controller
{
    public function __construct(protected MoodleService $moodle) {}

    private function getEstadoEntrega(array $submission, int $duedate): string
    {
        $status        = $submission['status'];
        $gradingstatus = $submission['gradingstatus'];
        $timemodified  = $submission['timemodified'];

        if ($status === 'new') {
            return 'no_entrego';
        }

        $tarde = $duedate > 0 && $timemodified > $duedate;

        if ($gradingstatus === 'graded') {
            return $tarde ? 'entrego_tarde_calificado' : 'entrego_calificado';
        }

        return $tarde ? 'entrego_tarde_pendiente' : 'entrego_pendiente';
    }

    public function index()
    {
        $cursosEaD      = $this->moodle->getCursosEaD();
        $cursosVisibles = collect($cursosEaD)->where('visible', 1)->values();

        $courseIds      = $cursosVisibles->pluck('id')->toArray();
        $todasLasTareas = $this->moodle->getTareasBatch($courseIds);

        $asignacionesPorCurso = collect($todasLasTareas['courses'] ?? [])->keyBy('id');

        $allAssignmentIds = [];
        $duedatePorTarea  = [];
        foreach ($asignacionesPorCurso as $cursoData) {
            foreach ($cursoData['assignments'] ?? [] as $tarea) {
                $allAssignmentIds[]            = $tarea['id'];
                $duedatePorTarea[$tarea['id']] = $tarea['duedate'] ?? 0;
            }
        }

        $entregasPorTarea = [];
        if (!empty($allAssignmentIds)) {
            $resultado = $this->moodle->getEntregasBatch($allAssignmentIds);
            foreach ($resultado['assignments'] ?? [] as $asign) {
                $entregasPorTarea[$asign['assignmentid']] = $asign['submissions'] ?? [];
            }
        }

        $resumen = $cursosVisibles->map(function ($curso) use ($asignacionesPorCurso, $entregasPorTarea, $duedatePorTarea) {
            $asignaciones = $asignacionesPorCurso->get($curso['id'])['assignments'] ?? [];

            $contadores = [
                'entrego_calificado'       => 0,
                'entrego_pendiente'        => 0,
                'entrego_tarde_calificado' => 0,
                'entrego_tarde_pendiente'  => 0,
                'no_entrego'               => 0,
            ];

            foreach ($asignaciones as $tarea) {
                $duedate     = $duedatePorTarea[$tarea['id']] ?? 0;
                $submissions = $entregasPorTarea[$tarea['id']] ?? [];

                foreach ($submissions as $sub) {
                    $contadores[$this->getEstadoEntrega($sub, $duedate)]++;
                }
            }

            return [
                'id'              => $curso['id'],
                'nombre'          => $curso['fullname']   ?? 'Sin nombre',
                'corto'           => $curso['shortname']  ?? '',
                'nombreCategoria' => $curso['categoryname'] ?? '—',
                'docente'         => $curso['contacts'][0]['fullname'] ?? '—',
                'tareas'          => count($asignaciones),

                'entrego_calificado'       => $contadores['entrego_calificado'],
                'entrego_pendiente'        => $contadores['entrego_pendiente'],
                'entrego_tarde_calificado' => $contadores['entrego_tarde_calificado'],
                'entrego_tarde_pendiente'  => $contadores['entrego_tarde_pendiente'],
                'no_entrego'               => $contadores['no_entrego'],

                'sin_calificar' => $contadores['entrego_pendiente']
                                 + $contadores['entrego_tarde_pendiente'],
            ];
        })->toArray();
        return view('calificaciones', compact('resumen'));
    }
}