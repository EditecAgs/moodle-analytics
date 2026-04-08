@extends('layouts.app')

@section('title', 'Calificaciones')

@section('actions')
    <span style="font-size:12px; color:#94a3b8;">
        Última actualización: {{ now()->format('d/m/Y H:i') }}
    </span>
@endsection

@section('content')

   
    <div class="metric-grid">
        <div class="metric-card green">
            <div class="metric-label">A tiempo y calificadas</div>
            <div class="metric-value">{{ collect($resumen)->sum('entrego_calificado') }}</div>
            <div class="metric-sub">entregas revisadas</div>
        </div>
        <div class="metric-card amber">
            <div class="metric-label">Sin calificar</div>
            <div class="metric-value">{{ collect($resumen)->sum('sin_calificar') }}</div>
            <div class="metric-sub">pendientes de revisión</div>
        </div>
        <div class="metric-card red">
            <div class="metric-label">No entregaron</div>
            <div class="metric-value">{{ collect($resumen)->sum('no_entrego') }}</div>
            <div class="metric-sub">sin ninguna entrega</div>
        </div>
        <div class="metric-card purple">
            <div class="metric-label">Tarde pero calificadas</div>
            <div class="metric-value">{{ collect($resumen)->sum('entrego_tarde_calificado') }}</div>
            <div class="metric-sub">entregaron fuera de fecha</div>
        </div>
    </div>

    {{-- Leyenda --}}
    <div style="display:flex; gap:1.2rem; flex-wrap:wrap; margin-bottom:1rem; font-size:11px; color:#64748b;">
        <span><span style="color:#15803d; font-weight:700;">●</span> A tiempo y calificado</span>
        <span><span style="color:#1d4ed8; font-weight:700;">●</span> A tiempo sin calificar</span>
        <span><span style="color:#b45309; font-weight:700;">●</span> Tarde calificado</span>
        <span><span style="color:#dc2626; font-weight:700;">●</span> Tarde sin calificar</span>
        <span><span style="color:#94a3b8; font-weight:700;">●</span> No entregó</span>
    </div>

    {{-- Tabla --}}
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <div>
                <div class="card-title" style="margin:0;">Estado de entregas por curso</div>
                <div style="font-size:12px; color:#94a3b8; margin-top:2px;">
                    Categorías EaD: 444 · 445 · 450
                </div>
            </div>
        </div>

        @if(empty($resumen))
            <div style="text-align:center; padding:2rem; color:#94a3b8; font-size:13px;">
                No se encontraron cursos.
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th style="text-align:center;">Categoría</th>
                            <th style="text-align:center;">Docente</th>
                            <th style="text-align:center;">Tareas</th>
                            <th style="text-align:center;" title="Entregó a tiempo y calificado">✅ A tiempo</th>
                            <th style="text-align:center;" title="Entregó a tiempo sin calificar">🔵 Sin calif.</th>
                            <th style="text-align:center;" title="Entregó tarde calificado">⚠️ Tarde calif.</th>
                            <th style="text-align:center;" title="Entregó tarde sin calificar">🔴 Tarde s/c</th>
                            <th style="text-align:center;" title="No entregó">❌ No entregó</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resumen as $curso)
                        <tr>
                            <td>
                                <div style="font-weight:500; color:#1a1a2e;">{{ $curso['nombre'] }}</div>
                                <div style="font-size:11px; color:#94a3b8;">{{ $curso['corto'] }}</div>
                            </td>
                            <td style="text-align:center;">
                                @php
                                    $catColors = [
                                        '2026A INDUSTRIAL'               => 'badge-purple',
                                        '2026A IGE'                      => 'badge-green',
                                        '2026A SISTEMAS COMPUTACIONALES' => 'badge-amber',
                                    ];
                                    $badgeClass = $catColors[$curso['nombreCategoria']] ?? 'badge-gray';
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $curso['nombreCategoria'] }}</span>
                            </td>
                            <td style="text-align:center; font-size:11px; color:#64748b;">
                                {{ $curso['docente'] }}
                            </td>
                            <td style="text-align:center; color:#64748b;">
                                {{ $curso['tareas'] }}
                            </td>

                            {{-- ✅ A tiempo calificado --}}
                            <td style="text-align:center;">
                                @if($curso['entrego_calificado'] > 0)
                                    <span class="badge badge-green">{{ $curso['entrego_calificado'] }}</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>

                            {{-- 🔵 A tiempo sin calificar --}}
                            <td style="text-align:center;">
                                @if($curso['entrego_pendiente'] > 0)
                                    <span class="badge" style="background:#dbeafe; color:#1d4ed8;">{{ $curso['entrego_pendiente'] }}</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>

                            {{-- ⚠️ Tarde calificado --}}
                            <td style="text-align:center;">
                                @if($curso['entrego_tarde_calificado'] > 0)
                                    <span class="badge badge-amber">{{ $curso['entrego_tarde_calificado'] }}</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>

                            {{-- 🔴 Tarde sin calificar --}}
                            <td style="text-align:center;">
                                @if($curso['entrego_tarde_pendiente'] > 0)
                                    <span class="badge badge-red">{{ $curso['entrego_tarde_pendiente'] }}</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>

                            {{-- ❌ No entregó --}}
                            <td style="text-align:center;">
                                @if($curso['no_entrego'] > 0)
                                    <span class="badge" style="background:#f1f5f9; color:#64748b;">{{ $curso['no_entrego'] }}</span>
                                @else
                                    <span class="badge badge-green">Al día</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8fafc; font-weight:600; font-size:13px;">
                            <td colspan="3" style="padding:0.65rem 0.85rem; color:#374151;">Totales</td>
                            <td style="text-align:center; padding:0.65rem 0.85rem; color:#374151;">
                                {{ collect($resumen)->sum('tareas') }}
                            </td>
                            <td style="text-align:center; padding:0.65rem 0.85rem; color:#15803d;">
                                {{ collect($resumen)->sum('entrego_calificado') }}
                            </td>
                            <td style="text-align:center; padding:0.65rem 0.85rem; color:#1d4ed8;">
                                {{ collect($resumen)->sum('entrego_pendiente') }}
                            </td>
                            <td style="text-align:center; padding:0.65rem 0.85rem; color:#b45309;">
                                {{ collect($resumen)->sum('entrego_tarde_calificado') }}
                            </td>
                            <td style="text-align:center; padding:0.65rem 0.85rem; color:#dc2626;">
                                {{ collect($resumen)->sum('entrego_tarde_pendiente') }}
                            </td>
                            <td style="text-align:center; padding:0.65rem 0.85rem; color:#64748b;">
                                {{ collect($resumen)->sum('no_entrego') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

@endsection 