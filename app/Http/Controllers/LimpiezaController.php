<?php

namespace App\Http\Controllers;

use App\Services\MoodleService;

class LimpiezaController extends Controller
{
    public function __construct(protected MoodleService $moodle) {}

    public function index()
    {
        // aqui traigo los cursos EaD visibles
        $cursosEaD     = $this->moodle->getCursosEaD();
        $cursosVisible = collect($cursosEaD)->where('visible', 1)->values();

        $cursosCmids  = [];

        foreach ($cursosVisible as $curso) {
            $cmids = $this->buscarLabels($curso['id']);
            

            if (!empty($cmids)) {
                $cursosCmids[] = [
                    'id'              => $curso['id'],
                    'nombre'          => $curso['fullname'],
                    'corto'           => $curso['shortname'],
                    'nombreCategoria' => $curso['categoryname'] ?? '—',
                    'cmids'           => $cmids['cmids'],
                    'labels'          => $cmids['labels'],
                    'total' => count($cmids['cmids']),
                ];
            }
        }

        $totalModulos = collect($cursosCmids)->sum('total');

        return view('function.limpieza', compact('cursosCmids', 'totalModulos'));
    }

    public function eliminar()
    {
        $cmids = request('cmids', []);

        if (empty($cmids)) {
            return back()->with('error', 'No hay módulos seleccionados.');
        }

        $cmids     = array_map('intval', $cmids);
        $resultado = $this->moodle->eliminarModulos($cmids);

        if (isset($resultado['error'])) {
            return back()->with('error', 'Error: ' . $resultado['error']);
        }


        foreach (request('curso_ids', []) as $cursoId) {
            $this->moodle->clearCache('core_course_get_contents', ['courseid' => (int) $cursoId]);
        }

        return back()->with('success', count($cmids) . ' módulos eliminados correctamente.');
    }


    private function buscarLabels(int $cursoId): array
    {
        $secciones = $this->moodle->getContenidoCurso($cursoId);
        $cmids     = [];
        $labels    = [];

        foreach ($secciones as $seccion) {
            foreach ($seccion['modules'] ?? [] as $modulo) {
                if ($modulo['modname'] !== 'label') continue;

                $desc = $modulo['description'] ?? '';
                $name = strtoupper(trim($modulo['name'] ?? ''));

                if (
                    str_contains($desc, '#103c7d') &&
                    (
                        //str_contains($name, 'RECURSOS') ||
                        str_contains($name, 'PRESENTACION') ||
                        str_contains($name, 'VIDEOCONFERENCIAS')
                    )
                ) {
                    $cmids[] = $modulo['id'];
                    $labels[] = [ 
                    'cmid' => $modulo['id'],
                    'name' => $modulo['name'],
                    'desc' => $desc,
                ];
                }
            }
        }

         return ['cmids' => $cmids, 'labels' => $labels];
    }
}