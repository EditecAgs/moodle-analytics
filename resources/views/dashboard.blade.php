@extends('layouts.app')

@section('title', 'Dashboard')

@section('actions')
    <span style="font-size:12px; color:#94a3b8;">
        Última actualización: {{ now()->format('d/m/Y H:i') }}
    </span>
@endsection

@section('content')


    <div class="metric-grid">
        <div class="metric-card purple">
            <div class="metric-label">Cursos activos</div>
            <div class="metric-value">{{ $cursosActivos }}</div>
            <div class="metric-sub">visibles para alumnos Ead</div>
        </div>
        <div class="metric-card green">
            <div class="metric-label">Cursos EaD</div>
            <div class="metric-value">{{ $totalCursos }}</div>
            <div class="metric-sub">categorías 444, 445, 450</div>
        </div>
    </div>

   
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <div>
                <div class="card-title" style="margin:0;">Tareas entregadas por curso</div>
                <div style="font-size:12px; color:#94a3b8; margin-top:2px;">
                    Categorías EaD: 444 · 445 · 450
                </div>
            </div>
        </div>

        @if(empty($resumenCursos))
            <div style="text-align:center; padding:2rem; color:#94a3b8; font-size:13px;">
                No se encontraron cursos en las categorías EaD.<br>
                <small>Verifica la URL y el token de Moodle en el archivo .env</small>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th style="text-align:center;">Categoría EaD</th>
                            <th style="text-align:center;">Docente</th>
                            <th style="text-align:center;">Creditos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resumenCursos as $curso)
                        <tr>
                            <td>
                                <div style="font-weight:500; color:#1a1a2e;">
                                    {{ $curso['nombre'] }}
                                </div>
                                <div style="font-size:11px; color:#94a3b8;">
                                    {{ $curso['corto'] }}
                                </div>
                            </td>   
                            <td style="text-align:center;">
                                @php
                                    $catColors = [
                                        '2026A INDUSTRIAL' => 'badge-purple',
                                        '2026A IGE' => 'badge-green',
                                        '2026A SISTEMAS COMPUTACIONALES' => 'badge-amber',
                                    ];
                                    $badgeClass = $catColors[$curso['nombreCategoria']] ?? 'badge-gray';
                                @endphp
                                <span class="badge {{ $badgeClass }}">
                                    {{ $curso['nombreCategoria'] }}
                                </span>
                            </td>
                            <td style="text-align:center; font-size:11px;">
                                {{ $curso['Docente'] }}
                            </td>
                            <td style="text-align:center; color:#64748b;">
                                {{ $curso['creditos'] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection