@extends('layouts.app')

@section('title', 'Reporte de Calificaciones')

@section('actions')
<div style="display: flex; gap: 1rem; align-items: center;">
    <span style="font-size:12px; color:#94a3b8;">
        Última actualización: {{ now()->format('d/m/Y H:i') }}
    </span>
    <a href="{{ route('calificaciones.descargar-pdf', request()->query()) }}" 
       target="_blank"
       style="background:#dc2626; color:white; padding:6px 12px; border-radius:6px; text-decoration:none; font-size:12px; display:inline-flex; align-items:center; gap:6px;">
        📄 Descargar PDF Rojos
    </a>
    
   <!-- <a href="{{ route('calificaciones.pdf.grafica', request()->query()) }}" 
   target="_blank"
   style="background:#059669; color:white; padding:6px 12px; border-radius:6px; text-decoration:none; font-size:12px; display:inline-flex; align-items:center; gap:6px;">
    🥧 Descargar gráfica de pastel
</a>-->
</div>
@endsection

@section('content')

{{-- SECCIÓN DE EVENTOS DESTACADOS (Curso 1507) - COLLAPSABLE --}}
@if(!empty($eventosFiltrados))
<div class="eventos-card" style="margin-bottom:1.5rem;">
    <details {{ request()->has('eventos_open') ? 'open' : '' }}>
        <summary style="cursor:pointer; list-style:none;">
            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                <span style="font-size:20px;">📅</span>
                <h3 style="margin:0; color:#1e293b; display:inline-block;">Eventos importantes - Curso 1507</h3>
                <span class="badge badge-blue">{{ count($eventosFiltrados) }} eventos</span>
                <span style="font-size:12px; color:#94a3b8; margin-left:auto;">▼ Haz clic para {{ request()->has('eventos_open') ? 'contraer' : 'expandir' }}</span>
            </div>
        </summary>
        <div style="margin-top:1rem;">
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
    </details>
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

{{-- CUADROS GENERALES DE RESULTADOS --}}
@if(isset($reporteAuditoria) && !isset($reporteAuditoria['error']))
@php
    $cursosConProblema = collect($reporteAuditoria['cursos'])->where('estado', 'error')->count();
    $cursosJustificados = collect($reporteAuditoria['cursos'])->where('estado', 'warning')->count();
    $cursosOk = collect($reporteAuditoria['cursos'])->where('estado', 'ok')->count();
    $cursosBien = $cursosOk + $cursosJustificados;
@endphp
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    {{-- Cuadro Rojo: Cursos con problema (entregaron a tiempo sin calificar) --}}
    <div style="background: linear-gradient(135deg, #fff1f1 0%, #fee2e2 100%); border-radius: 16px; padding: 1.25rem; border-left: 4px solid #ef4444; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div style="background: #ef4444; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 24px;">❌</span>
            </div>
            <div>
                <div style="font-size: 12px; color: #991b1b; text-transform: uppercase; font-weight: 600;">Cursos con problema</div>
                <div style="font-size: 32px; font-weight: 800; color: #dc2626;">{{ $cursosConProblema }}</div>
                <div style="font-size: 11px; color: #7f1d1d;">Entregaron a tiempo SIN calificar</div>
            </div>
        </div>
    </div>

    {{-- Cuadro Verde: Cursos bien (justificados + completados) --}}
    <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 16px; padding: 1.25rem; border-left: 4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div style="background: #10b981; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 24px;">✅</span>
            </div>
            <div>
                <div style="font-size: 12px; color: #166534; text-transform: uppercase; font-weight: 600;">Cursos bien</div>
                <div style="font-size: 32px; font-weight: 800; color: #16a34a;">{{ $cursosBien }}</div>
                <div style="font-size: 11px; color: #14532d;">Justificados + Completados</div>
            </div>
        </div>
    </div>

    {{-- Cuadro Total de cursos auditados --}}
    <div style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-radius: 16px; padding: 1.25rem; border-left: 4px solid #64748b; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div style="background: #64748b; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 24px;">📊</span>
            </div>
            <div>
                <div style="font-size: 12px; color: #334155; text-transform: uppercase; font-weight: 600;">Total auditados</div>
                <div style="font-size: 32px; font-weight: 800; color: #1e293b;">{{ count($reporteAuditoria['cursos']) }}</div>
                <div style="font-size: 11px; color: #475569;">Cursos con tema de corte</div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- SECCIÓN: Reporte de Auditoría Post-Academia (CORREGIDO) --}}
@if(isset($reporteAuditoria) && !isset($reporteAuditoria['error']))
<div class="auditoria-card" style="margin-bottom:1.5rem;">
    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem;">
        <span style="font-size:20px;">🔍</span>
        <h3 style="margin:0; color:#1e293b;">Reporte de Calificaciones</h3>
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
                    <div style="font-size:11px; color:#64748b; margin-top:2px;">📁 {{ $cursoAuditado['categoryname'] ?? 'Sin categoría' }}</div>
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
                            <tr style="background:#f1f5f9;">
                                <th style="padding:8px; text-align:left;">Tema</th>
                                <th style="padding:8px; text-align:center;">Estado</th>
                                <th style="padding:8px; text-align:center;">Calificados</th>
                                <th style="padding:8px; text-align:center;">Sin calificar</th>
                                <th style="padding:8px; text-align:center;">No entregaron</th>
                                <th style="padding:8px; text-align:center;">Justificados<br><span style="font-size:10px;">(entregaron tarde)</span></th>
                                <th style="padding:8px; text-align:center;">⚠️ Problema<br><span style="font-size:10px;">(entregaron a tiempo)</span></th>
                                <th style="padding:8px; text-align:left;">Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cursoAuditado['temas_auditados'] as $tema)
                            <tr style="border-bottom:1px solid #e2e8f0;
                                @if($tema['estado'] == 'ok') background:#f0fdf4;
                                @elseif($tema['estado'] == 'warning') background:#fefce8;
                                @else background:#fff1f1;
                                @endif">
                                <td style="padding:8px; font-weight:600;">Tema {{ $tema['tema_numero'] }}</td>
                                <td style="padding:8px; text-align:center;">
                                    @if($tema['estado'] == 'ok') ✅
                                    @elseif($tema['estado'] == 'warning') ⚠️
                                    @else ❌
                                    @endif
                                </td>
                                <td style="padding:8px; text-align:center;">{{ $tema['calificados'] ?? 'N/A' }}</td>
                                <td style="padding:8px; text-align:center;">{{ $tema['sin_calificar'] ?? 'N/A' }}</td>
                                <td style="padding:8px; text-align:center; color:#64748b;">{{ $tema['no_entregaron'] ?? 0 }}</td>
                                <td style="padding:8px; text-align:center; color:#f59e0b;">{{ $tema['justificados'] ?? 0 }}</td>
                                <td style="padding:8px; text-align:center; 
                                    @if(($tema['no_justificados'] ?? 0) > 0) 
                                        background:#fee2e2; color:#dc2626; font-weight:700; border-radius:4px;
                                    @else 
                                        color:#16a34a; 
                                    @endif">
                                    @if(($tema['no_justificados'] ?? 0) > 0)
                                        🔴 {{ $tema['no_justificados'] }}
                                    @else
                                        ✅ 0
                                    @endif
                                </td>
                                <td style="padding:8px; font-size:11px;">{{ $tema['mensaje'] }}</td>
                            </tr>
                            
                            {{-- Mostrar detalle de actividades si existe --}}
                            @if(!empty($tema['detalle']))
                            <tr style="background:#f8fafc;">
                                <td colspan="8" style="padding:8px 8px 8px 24px; font-size:11px;">
                                    <details>
                                        <summary style="cursor:pointer; color:#3b82f6;">📋 Detalle por actividad</summary>
                                        <table style="width:100%; margin-top:8px; border-collapse:collapse;">
                                            <thead>
                                                <tr style="background:#e2e8f0;">
                                                    <th style="padding:4px; text-align:left;">Actividad</th>
                                                    <th style="padding:4px; text-align:center;">Tipo</th>
                                                    <th style="padding:4px; text-align:center;">Sin calificar</th>
                                                    <th style="padding:4px; text-align:center;">No entregaron</th>
                                                    <th style="padding:4px; text-align:center;">Justificados<br>(tarde)</th>
                                                    <th style="padding:4px; text-align:center;">⚠️ Problema<br>(a tiempo)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($tema['detalle'] as $actividad)
                                                <tr style="border-bottom:1px solid #e2e8f0;">
                                                    <td style="padding:4px;">{{ $actividad['nombre'] }}</td>
                                                    <td style="padding:4px; text-align:center;">
                                                        @if($actividad['tipo'] == 'assign') Tarea
                                                        @elseif($actividad['tipo'] == 'forum') Foro
                                                        @elseif($actividad['tipo'] == 'quiz') Examen
                                                        @endif
                                                    </td>
                                                    <td style="padding:4px; text-align:center;">{{ $actividad['sin_calificar'] }}</td>
                                                    <td style="padding:4px; text-align:center;">{{ $actividad['no_entregaron'] }}</td>
                                                    <td style="padding:4px; text-align:center; color:#f59e0b;">{{ $actividad['justificados'] }}</td>
                                                    <td style="padding:4px; text-align:center; 
                                                        @if($actividad['no_justificados'] > 0) 
                                                            background:#fee2e2; color:#dc2626; font-weight:700; border-radius:4px;
                                                        @endif">
                                                        @if($actividad['no_justificados'] > 0)
                                                            🔴 {{ $actividad['no_justificados'] }}
                                                        @else
                                                            ✅ 0
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </details>
                                </td>
                            </tr>
                            @endif
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
        <span style="font-weight:600;">Error en Reporte de Calificaciones:</span>
        <span>{{ $reporteAuditoria['error'] }}</span>
    </div>
</div>
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
    
    /* Quitar el triángulo por defecto del summary */
    .eventos-card details summary {
        list-style: none;
    }
    
    .eventos-card details summary::-webkit-details-marker {
        display: none;
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