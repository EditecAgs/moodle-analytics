<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class MoodleService
{
    protected string $url;
    protected string $token;
    protected int $cacheTtl;
    protected string $endpoint;
    protected array $categoriasEad;
    protected bool $verifySsl;

        public function __construct()
        {
            $this->url           = rtrim(config('moodle.url'), '/');
            $this->token         = config('moodle.token');
            $this->cacheTtl      = (int) config('moodle.cache_ttl', 1800);
            $this->endpoint      = config('moodle.endpoint', '/webservice/rest/server.php');
            $this->categoriasEad = config('moodle.categorias_ead', [450]);
            $this->verifySsl     = config('moodle.verify_ssl', true);
        }

    // ─────────────────────────────────────────────────────────
    // CORE — llamadas HTTP
    // ─────────────────────────────────────────────────────────

    public function call(string $function, array $params = []): array
    {
        $cacheKey = 'moodle_' . md5($function . serialize($params));
        return Cache::remember($cacheKey, $this->cacheTtl, fn() => $this->fetch($function, $params));
    }

    public function callFresh(string $function, array $params = []): array
    {
        return $this->fetch($function, $params);
    }

    public function clearCache(string $function, array $params = []): void
    {
        Cache::forget('moodle_' . md5($function . serialize($params)));
    }

protected function fetch(string $function, array $params): array
{
    try {

        $verify = $this->verifySsl;

        // Token por defecto
        $token = $this->token;

        // Si es función de eventos, usar otro token
        if ($function === 'local_global_reports_topic_completion_grades_report') {
            $token = env('MOODLE_TOKEN_OTHERS');
        }
        if ($function === 'local_global_reports_access_teachers_report') {
            $token = env('MOODLE_TOKEN_OTHERS');
        }
        if ($function === 'local_global_reports_topic_completion_forum_report') {
            $token = env('MOODLE_TOKEN_OTHERS');
        }
        if ($function === 'local_global_reports_topic_completion_quiz_report') {
            $token = env('MOODLE_TOKEN_OTHERS');
        }

        $response = Http::withOptions(['verify' => $verify])
            ->timeout(200)
            ->get($this->url . $this->endpoint, array_merge([
                'wstoken'            => $token,
                'moodlewsrestformat' => 'json',
                'wsfunction'         => $function,
            ], $params));

        if ($response->failed()) {
            Log::error("Moodle API error [{$function}]: HTTP {$response->status()}");
            return [];
        }

        $data = $response->json();

        if (isset($data['exception'])) {
            Log::error("Moodle exception [{$function}]: " . json_encode($data));
            return [];
        }

        return is_array($data) ? $data : [];

    } catch (Exception $e) {
        Log::error("Moodle connection error [{$function}]: " . $e->getMessage());
        return [];
    }
}

    //funcion para endpoinds con post
    protected function post(string $function, array $params): array
    {
        try {
            $verify = $this->verifySsl;

            $url = $this->url . $this->endpoint 
                . '?wstoken=' . $this->token
                . '&moodlewsrestformat=json'
                . '&wsfunction=' . $function;

    $response = Http::withOptions(['verify' => $verify])
        ->timeout(30)
        ->asForm()
        ->post($url, $params);


            if ($response->failed()) {
                Log::error("Moodle API POST error [{$function}]: HTTP {$response->status()}");
                return [];
            }

            $data = $response->json();

            if (isset($data['exception'])) {
                Log::error("Moodle POST exception [{$function}]: " . ($data['message'] ?? 'Sin mensaje'));
                return ['error' => $data['message'] ?? 'Error desconocido'];
            }

            return is_array($data) ? $data : [];

        } catch (Exception $e) {
            Log::error("Moodle POST connection error [{$function}]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiengo TODOS los registros de un endpoint paginado (limit/offset).
     * Hace llamadas en lotes hasta que el servidor devuelva menos que $limit.
     */
    public function fetchAllPaginated(string $function, array $baseParams = [], int $limit = 100): array
    {
        $all    = [];
        $offset = 0;

        do {
            $params = array_merge($baseParams, [
                'limit'  => $limit,
                'offset' => $offset,
            ]);

            // Sin caché para siempre traer datos frescos en reportes
            $batch = $this->callFresh($function, $params);

            if (empty($batch)) break;

            $all    = array_merge($all, $batch);
            $offset += $limit;

        } while (count($batch) === $limit); // si trajo menos que $limit, ya no hay más

        return $all;
    }
    /**
     * Cursos de una categoría específica.
     */
    public function getCursosPorCategoria(int $categoryId): array
    {
        $result = $this->call('core_course_get_courses_by_field', [
            'field' => 'category',
            'value' => $categoryId,
        ]);
        return $result['courses'] ?? [];
    }

    /**
     * Todos los cursos de las categorías EaD (444, 445, 450).
     * Hace una llamada por categoría y combina los resultados.
     */
    public function getCursosEaD(): array
    {
        $todos = [];
        foreach ($this->categoriasEad as $catId) {
            $cursos = $this->getCursosPorCategoria($catId);
            $todos  = array_merge($todos, $cursos);
        }
        return $todos;
    }

    /**
     * Solo cursos EaD activos (visible = 1).
     */
    public function getCursosEaDActivos(): array
    {
        return collect($this->getCursosEaD())
            ->where('visible', 1)
            ->values()
            ->toArray();
    }

    /**
     * Devuelve las categorías EaD configuradas.
     */
    public function getCategoriasEaD(): array
    {
        return $this->categoriasEad;
    }

    // ─────────────────────────────────────────────────────────
    // USUARIOS
    // ─────────────────────────────────────────────────────────

    public function getUsuariosCurso(int $cursoId): array
    {
        return $this->call('core_enrol_get_enrolled_users', [
            'courseid' => $cursoId,
        ]);
    }

    public function getUsuariosPorCampo(string $campo, array $valores): array
    {
        $params = ['field' => $campo];
        foreach ($valores as $i => $valor) {
            $params["values[{$i}]"] = $valor;
        }
        return $this->call('core_user_get_users_by_field', $params);
    }

    // ─────────────────────────────────────────────────────────
    // TAREAS Y ENTREGAS (batch)
    // ─────────────────────────────────────────────────────────

    /**
     * Tareas de múltiples cursos en una sola llamada.
     */
    public function getTareasBatch(array $courseIds): array
    {
        $params = [];
        foreach ($courseIds as $i => $id) {
            $params["courseids[{$i}]"] = $id;
        }
        return $this->call('mod_assign_get_assignments', $params);
    }

    /**
     * Entregas de múltiples tareas en una sola llamada.
     */
    public function getEntregasBatch(array $assignmentIds): array
    {
        if (empty($assignmentIds)) return [];

        $params = [];
        foreach ($assignmentIds as $i => $id) {
            $params["assignmentids[{$i}]"] = $id;
        }
        return $this->call('mod_assign_get_submissions', $params);
    }

    /**
     * Tareas de un solo curso.
     */
    public function getTareasCurso(int $cursoId): array
    {
        return $this->getTareasBatch([$cursoId]);
    }

    /**
     * Entregas de una sola tarea.
     */
    public function getEntregasTarea(int $tareaId): array
    {
        return $this->getEntregasBatch([$tareaId]);
    }

    // ─────────────────────────────────────────────────────────
    // CALIFICACIONES
    // ─────────────────────────────────────────────────────────

    public function getCalificacionesCurso(int $cursoId): array
    {
        return $this->call('gradereport_overview_get_course_grades', [
            'courseid' => $cursoId,
        ]);
    }

    public function getCompletacionAlumno(int $cursoId, int $userId): array
    {
        return $this->call('core_completion_get_activities_completion_status', [
            'courseid' => $cursoId,
            'userid'   => $userId,
        ]);
    }


    public function getForosCurso(int $cursoId): array
    {
        return $this->call('mod_forum_get_forums_by_courses', [
            'courseids[0]' => $cursoId,
        ]);
    }

    public function getContenidoCurso(int $cursoId): array
    {
        return $this->call('core_course_get_contents', [
            'courseid' => $cursoId,
        ]);
    }

    public function getLabelsCurso(int $cursoId): array
    {
        $secciones = $this->getContenidoCurso($cursoId);
        $cmids     = [];
 
foreach ($secciones as $seccion) {
    foreach ($seccion['modules'] ?? [] as $modulo) {
        if ($modulo['modname'] !== 'label') continue;

        $desc = $modulo['description'] ?? '';
        $name = strtoupper(trim($modulo['name'] ?? ''));

        if (
            str_contains($desc, '#103c7d') &&
            (
                str_contains($name, 'RECURSOS') ||
                str_contains($name, 'ACTIVIDADES') ||
                str_contains($name, 'VIDEOCONFERENCIAS')
            )
        ) {
            $cmids[] = $modulo['id'];
        }
    }
}
 
        return $cmids;
    }
 
     // Elimino los mdulos por sus cmids.
public function eliminarModulos(array $cmids): array
{
    if (empty($cmids)) return [];

    $params = [];

    //el endpoind espera algo asi cmids[0]=123 cmids[1]=124 cmids[2]=125
    //si mando [123,124,125] no jala
    //por eso lo convierto
    foreach ($cmids as $i => $cmid) {
        $params["cmids[{$i}]"] = $cmid;
    }

    return $this->post('core_course_delete_modules', $params);
}

// ─────────────────────────────────────────────────────────
// EVENTOS (CALENDARIO)
// ─────────────────────────────────────────────────────────

public function getEventos(array $courseIds = [], ?int $from = null, ?int $to = null): array
{
    // Rango por defecto (desde inicio de cursos hasta hoy)
    $from = $from ?? Carbon::parse('2026-01-26')->startOfDay()->timestamp;
    $to   = $to   ?? Carbon::now()->endOfDay()->timestamp;

    // Configurar parámetros para el endpoint
    $params = [
        'events[eventids][0]'  => 0,          // todos los eventos
        'options[userevents]'  => 1,
        'options[siteevents]'  => 1,
        'options[timestart]'   => $from,
        'options[timeend]'     => $to,
    ];

    // Filtrar por cursos si se mandan
    if (!empty($courseIds)) {
        foreach ($courseIds as $i => $id) {
            $params["events[courseids][{$i}]"] = $id;
        }
    }

    $data = $this->call('core_calendar_get_calendar_events', $params);

    return $data['events'] ?? [];
}

// ─────────────────────────────────────────────────────────
// REPORTES DOCENTES
// ─────────────────────────────────────────────────────────

public function getReporteAccesosDocentes(array $categoryIds = []): array
{
    $categorias = empty($categoryIds) ? $this->categoriasEad : $categoryIds;

    $params = [];
    foreach ($categorias as $i => $id) {
        $params["categories[{$i}]"] = $id;
    }

    return $this->fetchAllPaginated(
        'local_global_reports_access_teachers_report',
        $params
    );
}
public function getDocentesCurso(int $courseId): array
{
    $usuarios = $this->call('core_enrol_get_enrolled_users', [
        'courseid' => $courseId,
    ]);


    $docentes = collect($usuarios)->filter(function ($user) {
        foreach ($user['roles'] ?? [] as $rol) {
            if (in_array($rol['shortname'], ['editingteacher', 'teacher'])) {
                return true;
            }
        }
        return false;
    })->values();

    return $docentes->toArray();
}
public function getReporteCalificaciones(array $categoryIds = []): array
{
    $categorias = empty($categoryIds) ? $this->categoriasEad : $categoryIds;

    $params = [];
    foreach ($categorias as $i => $id) {
        $params["categories[{$i}]"] = $id;
    }

    return $this->fetchAllPaginated(
        'local_global_reports_topic_completion_grades_report',
        $params
    );
}

/**
 * Obtener reporte de foros
 */
public function getReporteForos(array $categoryIds = []): array
{
    $categorias = empty($categoryIds) ? $this->categoriasEad : $categoryIds;

    $params = [];
    foreach ($categorias as $i => $id) {
        $params["categories[{$i}]"] = $id;
    }

    return $this->fetchAllPaginated(
        'local_global_reports_topic_completion_forum_report',
        $params
    );
}

/**
 * Obtener reporte de exámenes (quiz)
 */
public function getReporteExamenes(array $categoryIds = []): array
{
    $categorias = empty($categoryIds) ? $this->categoriasEad : $categoryIds;

    $params = [];
    foreach ($categorias as $i => $id) {
        $params["categories[{$i}]"] = $id;
    }

    return $this->fetchAllPaginated(
        'local_global_reports_topic_completion_quiz_report',
        $params
    );
}
    
}