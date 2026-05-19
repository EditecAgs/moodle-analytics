@extends('layouts.app')

@section('title', 'Reporte de Calificaciones')

@section('actions')
    <span style="font-size:12px; color:#94a3b8;">
        Última actualización: {{ now()->format('d/m/Y H:i') }}
    </span>
@endsection

@section('content')

{{-- SECCIÓN DE EVENTOS DESTACADOS (Curso 1507) --}}
@if(!empty($eventosFiltrados))
<div class="eventos-card" style="margin-bottom:1.5rem;">
    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem;">
        <span style="font-size:20px;">📅</span>
        <h3 style="margin:0; color:#1e293b;">Eventos importantes - Curso 1507</h3>
        <span class="badge badge-blue">{{ count($eventosFiltrados) }} eventos</span>
    </div>
    
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:1rem;">
        @foreach($eventosFiltrados as $evento)
        <div class="evento-item">
            <div class="evento-fecha">
                <span class="evento-dia">{{ \Carbon\Carbon::createFromTimestamp($evento['timestart'])->format('d') }}</span>
                <span class="evento-mes">{{ \Carbon\Carbon::createFromTimestamp($evento['timestart'])->isoFormat('MMM') }}</span>
            </div>
            <div class="evento-info">
                <div class="evento-titulo">
                    @if(str_contains($evento['name'], 'Cierre'))
                        🔒
                    @elseif(str_contains($evento['name'], 'Reunión'))
                        👥
                    @endif
                    {{ $evento['name'] }}
                </div>
                <div class="evento-fecha-hora">
                    📅 {{ \Carbon\Carbon::createFromTimestamp($evento['timestart'])->format('d/m/Y') }}
                    ⏰ {{ \Carbon\Carbon::createFromTimestamp($evento['timestart'])->format('H:i') }}
                    @if($evento['timeend'] != $evento['timestart'])
                        → {{ \Carbon\Carbon::createFromTimestamp($evento['timeend'])->format('H:i') }}
                    @endif
                </div>
                @if($evento['description'])
                <div class="evento-descripcion">
                    {{ Str::limit(strip_tags($evento['description']), 100) }}
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
{{-- Filtro categorías --}}
<form method="GET" style="margin-bottom:1.5rem; padding:1rem; background:#f8fafc; border-radius:8px;">
    <label style="font-weight:600; margin-right:1rem;">Filtrar por categorías:</label>
    <input type="checkbox" name="categories[]" value="444" id="cat444" {{ in_array('444', request('categories', [])) ? 'checked' : '' }}>
    <label for="cat444">444</label>
    
    <input type="checkbox" name="categories[]" value="445" id="cat445" {{ in_array('445', request('categories', [])) ? 'checked' : '' }}>
    <label for="cat445">445</label>
    
    <input type="checkbox" name="categories[]" value="450" id="cat450" {{ in_array('450', request('categories', [])) ? 'checked' : '' }}>
    <label for="cat450">450</label>
    
    <button type="submit" style="margin-left:1rem; padding:0.25rem 1rem; background:#3b82f6; color:white; border:none; border-radius:4px; cursor:pointer;">Filtrar</button>
    
    @if(request()->has('categories'))
        <a href="{{ route('calificaciones.index') }}" style="margin-left:0.5rem; padding:0.25rem 1rem; background:#64748b; color:white; border:none; border-radius:4px; text-decoration:none;">Limpiar</a>
    @endif
</form>
{{-- ✅ NUEVA SECCIÓN: Reporte de Auditoría Post-Academia --}}
@if(isset($reporteAuditoria) && !isset($reporteAuditoria['error']))
<div class="auditoria-card" style="margin-bottom:1.5rem;">
    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem;">
        <span style="font-size:20px;">🔍</span>
        <h3 style="margin:0; color:#1e293b;">Auditoría de Calificaciones</h3>
        <span class="badge badge-purple">Post-academia</span>
    </div>
    
    <div style="background:#f8fafc; border-radius:12px; padding:1rem; margin-bottom:1rem;">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:1rem;">
            <div>
                <span style="font-size:12px; color:#64748b;">📅 Última reunión de academia</span>
                <div style="font-weight:700; color:#1e293b;">{{ $reporteAuditoria['fecha_academia'] }}</div>
            </div>
            <div>
                <span style="font-size:12px; color:#64748b;">📆 Fecha de generación de reporte</span>
                <div style="font-weight:700; color:#1e293b;">{{ $reporteAuditoria['fecha_reporte'] }}</div>
            </div>
            <div>
                <span style="font-size:12px; color:#64748b;">📊 Cursos auditados</span>
                <div style="font-weight:700; color:#1e293b;">{{ count($reporteAuditoria['cursos']) }}</div>
            </div>
        </div>
    </div>
    
    <div style="display:grid; gap:1rem;">
        @foreach($reporteAuditoria['cursos'] as $cursoAuditado)
        <div class="auditoria-curso" style="background:white; border-radius:12px; padding:1rem; border-left:4px solid 
            @if($cursoAuditado['estado'] == 'ok') #10b981
            @elseif($cursoAuditado['estado'] == 'warning') #f59e0b
            @elseif($cursoAuditado['estado'] == 'error') #ef4444
            @else #64748b
            @endif
            box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.75rem;">
                <div>
                    <div style="font-weight:700; color:#1e293b;">{{ $cursoAuditado['curso'] }}</div>
                    <div style="font-size:12px; color:#64748b;">👨‍🏫 {{ $cursoAuditado['profesor'] }}</div>
                </div>
                <div>
                    @if($cursoAuditado['tema_corte'])
                        <span class="badge" style="background:#e0e7ff; color:#3730a3;">📌 Tema corte: {{ $cursoAuditado['tema_corte'] }}</span>
                        @if($cursoAuditado['fecha_cierre'])
                            <span class="badge" style="background:#fef3c7; color:#92400e;">📅 Cierre: {{ $cursoAuditado['fecha_cierre'] }}</span>
                        @endif
                    @else
                        <span class="badge" style="background:#fee2e2; color:#991b1b;">⚠️ Sin evento de cierre</span>
                    @endif
                </div>
            </div>
            
            <div style="margin-bottom:0.75rem;">
                <span class="badge 
                    @if($cursoAuditado['estado'] == 'ok') badge-green
                    @elseif($cursoAuditado['estado'] == 'warning') badge-amber
                    @elseif($cursoAuditado['estado'] == 'error') badge-red
                    @endif">
                    {{ $cursoAuditado['mensaje'] }}
                </span>
            </div>
            
            @if(!empty($cursoAuditado['temas_requeridos']))
            <details style="margin-top:0.5rem;">
                <summary style="cursor:pointer; font-size:12px; color:#3b82f6; font-weight:600;">
                    📋 Ver detalles de temas auditados (Temas {{ implode(', ', $cursoAuditado['temas_requeridos']) }})
                </summary>
                <div style="margin-top:0.75rem;">
                    <table style="width:100%; border-collapse:collapse; font-size:12px;">
                        <thead>
                            <tr><th style="padding:8px; text-align:left;">Tema</th>
                                <th style="padding:8px; text-align:center;">Estado</th>
                                <th style="padding:8px; text-align:center;">Calificados</th>
                                <th style="padding:8px; text-align:center;">Sin calificar</th>
                                <th style="padding:8px; text-align:center;">Entregados tarde</th>
                                <th style="padding:8px; text-align:left;">Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cursoAuditado['temas_auditados'] as $tema)
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <td style="padding:8px; font-weight:600;">Tema {{ $tema['tema_numero'] }}</td>
                                <td style="padding:8px; text-align:center;">
                                    @if($tema['estado'] == 'ok') ✅
                                    @elseif($tema['estado'] == 'warning') ⚠️
                                    @else ❌
                                    @endif
                                </td>
                                <td style="padding:8px; text-align:center;">{{ $tema['calificados'] ?? 'N/A' }}</td>
                                <td style="padding:8px; text-align:center; color:#f59e0b;">{{ $tema['sin_calificar'] ?? 'N/A' }}</td>
                                <td style="padding:8px; text-align:center;">{{ $tema['entregados_tarde'] ?? 'N/A' }}</td>
                                <td style="padding:8px; font-size:11px; color:#64748b;">{{ $tema['mensaje'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
            @endif
        </div>
        @endforeach
    </div>
</div>
@elseif(isset($reporteAuditoria['error']))
<div class="card" style="background:#fee2e2; border-left:4px solid #ef4444; margin-bottom:1.5rem;">
    <div style="display:flex; align-items:center; gap:0.5rem;">
        <span>⚠️</span>
        <span style="font-weight:600;">Error en auditoría:</span>
        <span>{{ $reporteAuditoria['error'] }}</span>
    </div>
</div>
@endif

{{-- KPIs --}}
<div class="metric-grid">
    <div class="metric-card purple">
        <div class="metric-label">Total cursos</div>
        <div class="metric-value">{{ $totalCursos }}</div>
    </div>
    <div class="metric-card green">
        <div class="metric-label">Aprobados</div>
        <div class="metric-value">{{ $totalAprobado }}</div>
    </div>
    <div class="metric-card red">
        <div class="metric-label">Reprobados</div>
        <div class="metric-value">{{ $totalReprobado }}</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Sin calificar</div>
        <div class="metric-value">{{ $totalSinCalificar }}</div>
    </div>
</div>


@if($cursos->isEmpty())
    <div class="card" style="text-align:center; padding:2rem; color:#94a3b8;">
        No se encontraron datos para los filtros seleccionados.
    </div>
@else
    @foreach($cursos as $curso)
    <div class="card" style="margin-bottom:2rem;">
        
        {{-- Encabezado del curso --}}
        <div style="margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:2px solid #e2e8f0;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.5rem;">
                <div>
                    <div style="font-size:18px; font-weight:700; color:#1a1a2e;">
                        {{ $curso['curso'] }}
                    </div>
                    <div style="font-size:13px; color:#64748b; margin-top:4px;">
                        <span style="background:#e2e8f0; padding:2px 8px; border-radius:4px;">
                            📁 {{ $curso['categoryname'] }}
                        </span>
                        <span style="margin-left:8px;">Docente: {{ $curso['profesor'] }}</span>
                        <span style="margin-left:8px;">Alumnos: {{ $curso['total_alumnos'] }}</span>
                    </div>
                </div>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                    <span class="badge badge-green">✓ {{ $curso['totales']['aprobados'] }} aprobados</span>
                    <span class="badge badge-red">✗ {{ $curso['totales']['reprobados'] }} reprobados</span>
                    <span class="badge badge-amber">⏳ {{ $curso['totales']['total_sin_calificar'] }} sin calificar</span>
                    <span class="badge badge-purple">🔄 {{ $curso['totales']['total_reabiertos'] }} reabiertos</span>
                </div>
            </div>
        </div>
        
        {{-- Tabla por temas --}}
        <div class="table-wrap">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="background:#f1f5f9; border-bottom:2px solid #cbd5e1;">
                        <th style="text-align:left; padding:12px;">Tema</th>
                        <th style="text-align:center; padding:12px;">Total alumnos</th>
                        <th style="text-align:center; padding:12px;">Calificados</th>
                        <th style="text-align:center; padding:12px;">Sin calificar</th>
                        <th style="text-align:center; padding:12px;">Reabiertos</th>
                        <th style="text-align:center; padding:12px;">Entregados</th>
                        <th style="text-align:center; padding:12px;">No entregados</th>
                        <th style="text-align:center; padding:12px;">Aprobados</th>
                        <th style="text-align:center; padding:12px;">Reprobados</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($curso['temas'] as $tema)
                    @php
                        $bgColor = $loop->iteration % 2 == 0 ? '#f8fafc' : 'white';
                    @endphp
                    <tr style="background:{{ $bgColor }}; border-bottom:1px solid #e2e8f0;">
                        <td style="padding:12px;">
                            <strong>Tema {{ $tema['tema_numero'] }}</strong><br>
                            <span style="font-size:11px; color:#64748b;">{{ $tema['tema'] }}</span>
                        </td>
                        <td style="text-align:center; padding:12px; font-weight:600;">{{ $tema['total_alumnos'] }}</td>
                        <td style="text-align:center; padding:12px; color:#16a34a; font-weight:600;">{{ $tema['total_calificados'] }}</td>
                        <td style="text-align:center; padding:12px; color:#f59e0b;">{{ $tema['total_sin_calificar'] }}</td>
                        <td style="text-align:center; padding:12px; color:#8b5cf6;">{{ $tema['total_reabiertos'] }}</td>
                        <td style="text-align:center; padding:12px;">{{ $tema['total_entregados'] }}</td>
                        <td style="text-align:center; padding:12px; color:#dc2626;">{{ $tema['total_no_entregados'] }}</td>
                        <td style="text-align:center; padding:12px; color:#16a34a;">{{ $tema['aprobados'] }}</td>
                        <td style="text-align:center; padding:12px; color:#dc2626;">{{ $tema['reprobados'] }}</td>
                    </tr>
                    @endforeach
                    
                    {{-- Fila de totales del curso --}}
                    <tr style="background:#e2e8f0; font-weight:700; border-top:2px solid #94a3b8;">
                        <td style="padding:12px;"><strong>TOTAL CURSO</strong></td>
                        <td style="text-align:center; padding:12px;">{{ $curso['total_alumnos'] }}</td>
                        <td style="text-align:center; padding:12px; color:#16a34a;">{{ $curso['totales']['total_calificados'] }}</td>
                        <td style="text-align:center; padding:12px; color:#f59e0b;">{{ $curso['totales']['total_sin_calificar'] }}</td>
                        <td style="text-align:center; padding:12px;">{{ $curso['totales']['total_reabiertos'] }}</td>
                        <td style="text-align:center; padding:12px;">{{ $curso['totales']['total_entregados'] }}</td>
                        <td style="text-align:center; padding:12px; color:#dc2626;">{{ $curso['totales']['total_no_entregados'] }}</td>
                        <td style="text-align:center; padding:12px; color:#16a34a;">{{ $curso['totales']['aprobados'] }}</td>
                        <td style="text-align:center; padding:12px; color:#dc2626;">{{ $curso['totales']['reprobados'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        {{-- Expandir para ver actividades (opcional) --}}
        <details style="margin-top:1rem;">
            <summary style="cursor:pointer; color:#3b82f6; font-size:12px; font-weight:600;">📋 Ver actividades por tema</summary>
            <div style="margin-top:1rem;">
                @foreach($curso['temas'] as $tema)
                <div style="margin-bottom:1.5rem;">
                    <div style="font-weight:600; margin-bottom:0.5rem;">Tema {{ $tema['tema_numero'] }}: {{ $tema['tema'] }}</div>
                    <table style="width:100%; border-collapse:collapse; font-size:12px;">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th style="padding:8px; text-align:left;">Actividad</th>
                                <th style="padding:8px; text-align:center;">Tipo</th>
                                <th style="padding:8px; text-align:center;">Entregados</th>
                                <th style="padding:8px; text-align:center;">No entregados</th>
                                <th style="padding:8px; text-align:center;">Calificados</th>
                                <th style="padding:8px; text-align:center;">Sin calificar</th>
                                <th style="padding:8px; text-align:center;">Reabiertos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tema['actividades'] as $act)
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <td style="padding:8px;">{{ $act['actividad_nombre'] }}</td>
                                <td style="text-align:center; padding:8px;">{{ $act['tipo_modulo'] }}</td>
                                <td style="text-align:center; padding:8px;">{{ $act['entregado_a_tiempo'] + $act['entregado_tarde'] }}</td>
                                <td style="text-align:center; padding:8px;">{{ $act['no_entregado'] }}</td>
                                <td style="text-align:center; padding:8px; color:#16a34a;">{{ $act['calificados'] }}</td>
                                <td style="text-align:center; padding:8px; color:#f59e0b;">{{ $act['sin_calificar'] }}</td>
                                <td style="text-align:center; padding:8px;">{{ $act['reopened'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endforeach
            </div>
        </details>
        
    </div>
    @endforeach
@endif

@endsection

{{-- Estilos adicionales --}}
<style>
    .metric-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .metric-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-left: 4px solid;
    }
    
    .metric-card.purple { border-left-color: #8b5cf6; }
    .metric-card.green { border-left-color: #10b981; }
    .metric-card.red { border-left-color: #ef4444; }
    .metric-card.amber { border-left-color: #f59e0b; }
    
    .metric-label {
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .metric-value {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-red { background: #fee2e2; color: #991b1b; }
    .badge-amber { background: #fef3c7; color: #92400e; }
    .badge-purple { background: #f3e8ff; color: #6b21a5; }
    .badge-blue { background: #dbeafe; color: #1e40af; }
    
    .table-wrap {
        overflow-x: auto;
    }
    
    details summary {
        user-select: none;
    }
    
    details summary:hover {
        opacity: 0.8;
    }
    
    /* Estilos para eventos */
    .eventos-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 1.5rem;
        color: white;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }
    
    .eventos-card h3 {
        color: white;
    }
    
    .evento-item {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        gap: 1rem;
        transition: transform 0.2s, box-shadow 0.2s;
        color: #1e293b;
    }
    
    .evento-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
    }
    
    .evento-fecha {
        text-align: center;
        min-width: 60px;
        background: #f1f5f9;
        border-radius: 8px;
        padding: 0.5rem;
    }
    
    .evento-dia {
        display: block;
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
    }
    
    .evento-mes {
        display: block;
        font-size: 11px;
        text-transform: uppercase;
        color: #64748b;
        margin-top: 4px;
    }
    
    .evento-info {
        flex: 1;
    }
    
    .evento-titulo {
        font-weight: 700;
        font-size: 14px;
        color: #1e293b;
        margin-bottom: 4px;
    }
    
    .evento-fecha-hora {
        font-size: 11px;
        color: #64748b;
        margin-bottom: 4px;
    }
    
    .evento-descripcion {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 4px;
    }
    
    /* Estilos para auditoría */
    .auditoria-card {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }
    
    .auditoria-curso {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .auditoria-curso:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
    }
</style>