@extends('layouts.app')

@section('title', 'Reporte de Accesos Docentes')

@section('actions')
    <div style="display:flex; align-items:center; gap:1rem;">
        <span style="font-size:12px; color:#94a3b8;">
            Última actualización: {{ now()->format('d/m/Y H:i') }}
        </span>
        <a href="{{ route('docentes.pdf', request()->query()) }}"
           target="_blank"
           style="
               display:inline-flex; align-items:center; gap:6px;
               background:#dc2626; color:#fff;
               padding:6px 14px; border-radius:6px;
               font-size:12px; font-weight:600;
               text-decoration:none;
               transition: background .2s;
           "
           onmouseover="this.style.background='#b91c1c'"
           onmouseout="this.style.background='#dc2626'">
            ⬇ Descargar críticos PDF
        </a>
        <a href="{{ route('docentes.pdf.all', request()->query()) }}"
   target="_blank"
   style="
       display:inline-flex; align-items:center; gap:6px;
       background:#1a1a2e; color:#fff;
       padding:6px 14px; border-radius:6px;
       font-size:12px; font-weight:600;
       text-decoration:none;
       transition: background .2s;
   "
   onmouseover="this.style.background='#2d2d4e'"
   onmouseout="this.style.background='#1a1a2e'">
    ⬇ Descargar completo PDF
</a>
    </div>
@endsection

@section('content')

    @php
        $cursosFiltrados = collect($cursos)
            ->where('creditos', '>', 0)
            ->values();
    @endphp

    {{-- KPIs — promedio con cap en 100 --}}
    @php
        $total = $cursosFiltrados->count();

        $criticos = $cursosFiltrados
            ->where('porcentaje_accesos', '<=', 60)
            ->count();

        $buenos = $cursosFiltrados
            ->where('porcentaje_accesos', '>', 80)
            ->count();

        // El cap ya viene del controlador (max 100), pero por si acaso:
        $promedio = $total > 0
            ? round(
                $cursosFiltrados->avg(fn($c) => min((float)$c['porcentaje_accesos'], 100)),
                1
              )
            : 0;
    @endphp

    <div class="metric-grid">
        <div class="metric-card purple">
            <div class="metric-label">Total cursos</div>
            <div class="metric-value">{{ $total }}</div>
            <div class="metric-sub">con créditos válidos</div>
        </div>
        <div class="metric-card red">
            <div class="metric-label">Acceso crítico</div>
            <div class="metric-value">{{ $criticos }}</div>
            <div class="metric-sub">≤ 60 %</div>
        </div>
        <div class="metric-card green">
            <div class="metric-label">Promedio accesos</div>
            <div class="metric-value">{{ $promedio }} %</div>
            <div class="metric-sub">todos los cursos</div>
        </div>
    </div>

    <form method="GET" style="margin-bottom:1rem;">
        <label>Categorías:</label><br>
        <input type="checkbox" name="categories[]" value="444" {{ in_array('444', request('categories', [])) ? 'checked' : '' }}> 444
        <input type="checkbox" name="categories[]" value="445" {{ in_array('445', request('categories', [])) ? 'checked' : '' }}> 445
        <input type="checkbox" name="categories[]" value="450" {{ in_array('450', request('categories', [])) ? 'checked' : '' }}> 450
        <button type="submit">Filtrar</button>
    </form>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <div style="font-size:11px; color:#94a3b8;">
                <span style="display:inline-block; width:10px; height:10px; background:#fee2e2; border:1px solid #fca5a5; border-radius:2px; margin-right:4px;"></span> Crítico ≤ 60 %
                &nbsp;
                <span style="display:inline-block; width:10px; height:10px; background:#fef9c3; border:1px solid #fde047; border-radius:2px; margin-right:4px;"></span> Medio 61–80 %
            </div>
        </div>

        @if($cursosFiltrados->isEmpty())
            <div style="text-align:center; padding:2rem; color:#94a3b8; font-size:13px;">
                No se encontraron datos en el reporte.<br>
                <small>Verifica el token, categorías o créditos</small>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Docente</th>
                            <th style="text-align:center;">Categoría</th>
                            <th style="text-align:center;">Semanas hábiles</th>
                            <th style="text-align:center;">Ingresos reales</th>
                            <th style="text-align:center;">Ingresos esperados</th>
                            <th style="text-align:center;">% Accesos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cursosFiltrados as $curso)
                            @php
                                $pct = min((float) $curso['porcentaje_accesos'], 100);

                                if ($pct <= 60) {
                                    $rowBg    = '#fff1f1';
                                    $pctColor = '#dc2626';
                                } elseif ($pct <= 80) {
                                    $rowBg    = '#fefce8';
                                    $pctColor = '#ca8a04';
                                } else {
                                    $rowBg    = '#f0fdf4';
                                    $pctColor = '#16a34a';
                                }
                            @endphp
                            <tr style="background:{{ $rowBg }};">
                                <td>
                                    <div style="font-weight:500; color:#1a1a2e;">{{ $curso['nombre_curso'] }}</div>
                                    <div style="font-size:11px; color:#94a3b8;">ID {{ $curso['courseid'] }}</div>
                                </td>
                                <td style="color:#64748b;">{{ $curso['docente_nombre'] ?? 'Sin asignar' }}</td>
                                <td style="text-align:center;">
                                    @php
                                        $catColors = [444 => 'badge-purple', 445 => 'badge-green', 450 => 'badge-amber'];
                                        $badgeClass = $catColors[$curso['category']] ?? 'badge-gray';
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $curso['category'] }}</span>
                                </td>
                                <td style="text-align:center; color:#64748b;">{{ $curso['semanas_habiles'] }}</td>
                                <td style="text-align:center; color:#64748b;">{{ $curso['ingresos_reales'] }}</td>
                                <td style="text-align:center; color:#64748b;">{{ $curso['ingresos_esperados'] }}</td>
                                <td style="text-align:center;">
                                    <div style="display:flex; align-items:center; justify-content:center; gap:6px;">
                                        <div style="width:60px; height:6px; background:#e2e8f0; border-radius:999px; overflow:hidden;">
                                            <div style="width:{{ $pct }}%; height:100%; background:{{ $pctColor }}; border-radius:999px;"></div>
                                        </div>
                                        <span style="font-weight:600; color:{{ $pctColor }}; font-size:13px;">
                                            {{ number_format($pct, 1) }} %
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection