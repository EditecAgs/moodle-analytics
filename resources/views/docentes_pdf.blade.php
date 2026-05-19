<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a1a2e;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #dc2626;
        }

        .header h1 {
            font-size: 16px;
            font-weight: 700;
            color: #dc2626;
        }

        .header .meta {
            font-size: 9px;
            color: #64748b;
            text-align: right;
        }

        .badge-critico {
            display: inline-block;
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
            border-radius: 4px;
            padding: 1px 6px;
            font-size: 9px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        thead tr {
            background: #1a1a2e;
            color: #fff;
        }

        thead th {
            padding: 7px 8px;
            text-align: left;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        thead th.center { text-align: center; }

        tbody tr {
            background: #fff1f1;
            border-bottom: 1px solid #fecaca;
        }

        tbody tr:nth-child(even) {
            background: #fff5f5;
        }

        tbody td {
            padding: 6px 8px;
            vertical-align: middle;
        }

        tbody td.center { text-align: center; }

        .curso-nombre { font-weight: 600; font-size: 10px; }
        .curso-id     { font-size: 8px; color: #94a3b8; }

        .pct-wrap {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .pct-bar-bg {
            width: 50px;
            height: 5px;
            background: #fecaca;
            border-radius: 999px;
            overflow: hidden;
            display: inline-block;
        }

        .pct-bar-fill {
            height: 100%;
            background: #dc2626;
            border-radius: 999px;
            display: block;
        }

        .pct-text {
            font-weight: 700;
            color: #dc2626;
        }

        .footer {
            margin-top: 14px;
            font-size: 8px;
            color: #94a3b8;
            text-align: right;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }

        .summary {
            margin-bottom: 12px;
            padding: 8px 12px;
            background: #fff1f1;
            border-left: 3px solid #dc2626;
            border-radius: 4px;
        }

        .summary span {
            font-weight: 700;
            color: #dc2626;
        }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h1>⚠ Reporte de Accesos Críticos</h1>
            <div style="font-size:9px; color:#64748b; margin-top:3px;">
                Docentes con porcentaje de acceso ≤ 60 %
            </div>
        </div>
        <div class="meta">
            Generado: {{ now()->format('d/m/Y H:i') }}<br>
            TecNM Campus Aguascalientes
        </div>
    </div>

    <div class="summary">
        Se encontraron <span>{{ count($cursos) }}</span> curso(s) en estado crítico.
    </div>

    @if(empty($cursos))
        <p style="text-align:center; color:#94a3b8; margin-top:2rem;">
            No hay cursos en estado crítico.
        </p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Docente</th>
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
                @endphp
                <tr>
                    <td>
                        <div class="curso-nombre">{{ $curso['nombre_curso'] }}</div>
                        <div class="curso-id">ID {{ $curso['courseid'] }}</div>
                    </td>
                    <td>{{ $curso['docente_nombre'] ?? 'Sin asignar' }}</td>
                    <td class="center">{{ $curso['semanas_habiles'] }}</td>
                    <td class="center">{{ $curso['ingresos_reales'] }}</td>
                    <td class="center">{{ $curso['ingresos_esperados'] }}</td>
                    <td class="center">
                        <div class="pct-wrap">
                            <div class="pct-bar-bg">
                                <span class="pct-bar-fill" style="width:{{ $pct }}%;"></span>
                            </div>
                            <span class="pct-text">{{ number_format($pct, 1) }}%</span>
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