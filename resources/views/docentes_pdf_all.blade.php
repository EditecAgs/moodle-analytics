<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a2e; padding: 20px; }

        .header { display: flex; justify-content: space-between; align-items: flex-start;
                  margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #1a1a2e; }
        .header h1 { font-size: 16px; font-weight: 700; color: #1a1a2e; }
        .header .meta { font-size: 9px; color: #64748b; text-align: right; }

        .summary { margin-bottom: 12px; padding: 8px 12px; background: #f1f5f9;
                   border-left: 3px solid #1a1a2e; border-radius: 4px; }
        .summary span { font-weight: 700; }

        .legend { display: flex; gap: 16px; margin-bottom: 10px; font-size: 9px; }
        .legend-item { display: flex; align-items: center; gap: 4px; }
        .legend-dot { width: 10px; height: 10px; border-radius: 2px; }

        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        thead tr { background: #1a1a2e; color: #fff; }
        thead th { padding: 7px 8px; text-align: left; font-size: 9px; font-weight: 600;
                   text-transform: uppercase; letter-spacing: .04em; }
        thead th.center { text-align: center; }

        .row-red    { background: #fff1f1; border-bottom: 1px solid #fecaca; }
        .row-yellow { background: #fefce8; border-bottom: 1px solid #fde68a; }
        .row-green  { background: #f0fdf4; border-bottom: 1px solid #bbf7d0; }

        tbody td { padding: 6px 8px; vertical-align: middle; }
        tbody td.center { text-align: center; }

        .curso-nombre { font-weight: 600; font-size: 10px; }
        .curso-id     { font-size: 8px; color: #94a3b8; }

        .pct-wrap { display: flex; align-items: center; gap: 5px; }
        .pct-bar-bg { width: 50px; height: 5px; border-radius: 999px; overflow: hidden; display: inline-block; }
        .pct-text  { font-weight: 700; font-size: 10px; }

        .footer { margin-top: 14px; font-size: 8px; color: #94a3b8; text-align: right;
                  border-top: 1px solid #e2e8f0; padding-top: 6px; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h1>Reporte Completo de Accesos Docentes</h1>
            <div style="font-size:9px; color:#64748b; margin-top:3px;">
                Todos los cursos — ordenados por criticidad
            </div>
        </div>
        <div class="meta">
            Generado: {{ now()->format('d/m/Y H:i') }}<br>
            TecNM Campus Aguascalientes
        </div>
    </div>

    <div class="summary">
        Total: <span>{{ count($cursos) }}</span> curso(s) con créditos válidos.
    </div>

    <div class="legend">
        <div class="legend-item">
            <div class="legend-dot" style="background:#fecaca; border:1px solid #f87171;"></div>
            Crítico ≤ 60 %
        </div>
        <div class="legend-item">
            <div class="legend-dot" style="background:#fde68a; border:1px solid #fbbf24;"></div>
            Medio 61–80 %
        </div>
        <div class="legend-item">
            <div class="legend-dot" style="background:#bbf7d0; border:1px solid #4ade80;"></div>
            Bueno > 80 %
        </div>
    </div>

    @if(empty($cursos))
        <p style="text-align:center; color:#94a3b8; margin-top:2rem;">No hay cursos registrados.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Docente</th>
                    <th class="center">Categoría</th>
                    <th class="center">Sem. hábiles</th>
                    <th class="center">Ing. reales</th>
                    <th class="center">Ing. esperados</th>
                    <th class="center">% Accesos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cursos as $curso)
                @php
                    $pct = min((float) $curso['porcentaje_accesos'], 100);

                    if ($pct <= 60) {
                        $rowClass = 'row-red';
                        $barBg    = '#fecaca';
                        $barFill  = '#dc2626';
                        $pctColor = '#dc2626';
                    } elseif ($pct <= 80) {
                        $rowClass = 'row-yellow';
                        $barBg    = '#fde68a';
                        $barFill  = '#ca8a04';
                        $pctColor = '#ca8a04';
                    } else {
                        $rowClass = 'row-green';
                        $barBg    = '#bbf7d0';
                        $barFill  = '#16a34a';
                        $pctColor = '#16a34a';
                    }
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>
                        <div class="curso-nombre">{{ $curso['nombre_curso'] }}</div>
                        <div class="curso-id">ID {{ $curso['courseid'] }}</div>
                    </td>
                    <td>{{ $curso['docente_nombre'] ?? 'Sin asignar' }}</td>
                    <td class="center">{{ $curso['category'] }}</td>
                    <td class="center">{{ $curso['semanas_habiles'] }}</td>
                    <td class="center">{{ $curso['ingresos_reales'] }}</td>
                    <td class="center">{{ $curso['ingresos_esperados'] }}</td>
                    <td class="center">
                        <div class="pct-wrap">
                            <div class="pct-bar-bg" style="background:{{ $barBg }};">
                                <span style="display:block; width:{{ $pct }}%; height:100%;
                                             background:{{ $barFill }}; border-radius:999px;"></span>
                            </div>
                            <span class="pct-text" style="color:{{ $pctColor }};">
                                {{ number_format($pct, 1) }}%
                            </span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Sistema de Monitoreo EaD · TecNM Campus Aguascalientes
    </div>

</body>
</html>