<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocentesController;
use App\Http\Controllers\AlumnosController;
use App\Http\Controllers\CursosController;
use App\Http\Controllers\CalificacionesController;
use App\Http\Controllers\LimpiezaController;
use App\Http\Middleware\SimpleAuthMiddleware;

// ─── Login ────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── Dashboard (protegido) ────────────────────────────
Route::middleware(SimpleAuthMiddleware::class)->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Calificaciones
    
    Route::get('/calificaciones', [CalificacionesController::class, 'index'])->name('calificaciones.index');
    Route::get('calificaciones/pdf', [CalificacionesController::class, 'descargarPDF'])->name('calificaciones.descargar-pdf');
    Route::get('/calificaciones/pdf-grafica', [CalificacionesController::class, 'descargarPDFGrafica'])->name('calificaciones.pdf.grafica');
    Route::get('/calificaciones/chart-image', [ChartImageController::class, 'generarGraficaPastel'])->name('calificaciones.chart-image');
    // Docentes
    Route::get('/docentes',            [DocentesController::class, 'index'])->name('docentes.index');
    Route::get('/docentes/export-pdf', [DocentesController::class, 'exportPdf'])->name('docentes.pdf');
    Route::get('/docentes/pdf-all', [DocentesController::class, 'exportPdfAll'])
    ->name('docentes.pdf.all');
    Route::get('/docentes/pdf/barchart', [DocentesController::class, 'exportPdfBarChart'])->name('docentes.pdf.barchart');

    // Alumnos
    Route::get('/alumnos/riesgo', [AlumnosController::class, 'riesgo'])->name('alumnos.riesgo');
    Route::get('/alumnos/sin-acceso', [AlumnosController::class, 'sinAcceso'])->name('alumnos.sin-acceso');

    // Cursos
    Route::get('/cursos', [CursosController::class, 'index'])->name('cursos.index');
    Route::get('/cursos/{id}/reprobados', [CursosController::class, 'reprobados'])->name('cursos.reprobados');
    Route::get('/cursos/{id}/tareas-sin-calificar', [CursosController::class, 'tareasSinCalificar'])->name('cursos.tareas');

    // Limpiar caché manualmente
    Route::post('/cache/limpiar', [DashboardController::class, 'limpiarCache'])->name('cache.limpiar');
    // Eliminar etiquetas de tareas
    Route::get('/limpieza', [LimpiezaController::class, 'index'])->name('limpieza.index');
    Route::post('/limpieza/eliminar', [LimpiezaController::class, 'eliminar'])->name('limpieza.eliminar');

});
