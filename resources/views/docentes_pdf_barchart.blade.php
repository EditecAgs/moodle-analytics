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
            background: #f0f4f8;
            padding: 20px;
        }

        .main-container {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 0;
        }

        /* ── HEADER ── */
        .header {
            background: #1a1a2e;
            padding: 18px 25px;
            border-bottom: 3px solid #334155;
        }
        .header-inner {
            width: 100%;
        }
        .header-left {
            display: inline-block;
            width: 65%;
            vertical-align: top;
        }
        .header-right {
            display: inline-block;
            width: 32%;
            vertical-align: top;
            text-align: right;
        }
        .header h1 {
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.3px;
        }
        .header h1 small {
            font-size: 9px;
            font-weight: 400;
            color: #94a3b8;
            display: block;
            margin-top: 4px;
        }
        .header .meta {
            font-size: 8.5px;
            color: #cbd5e1;
            line-height: 1.7;
        }

        /* ── SUMMARY BANNER ── */
        .summary {
            background: #4f46e5;
            padding: 10px 25px;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
        }
        .summary span {
            font-size: 16px;
            font-weight: 800;
        }

        /* ── BODY PADDING ── */
        .body-pad {
            padding: 20px 25px;
        }

        /* ── STAT CARDS usando tabla ── */
        .stats-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 25px;
        }
        .stats-table td {
            width: 20%;
            padding: 14px 6px 12px;
            text-align: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .stat-number {
            font-size: 26px;
            font-weight: 800;
            line-height: 1.1;
            display: block;
        }
        .stat-label {
            font-size: 8px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 4px;
            display: block;
        }
        .stat-stripe {
            height: 4px;
            width: 100%;
            display: block;
            margin-bottom: 8px;
        }

        /* ── SECCIÓN DE GRÁFICA ── */
        .chart-section {
            border: 1px solid #e2e8f0;
            padding: 20px 15px 15px;
            margin-bottom: 25px;
            background: #fafcff;
        }
        .chart-title {
            font-size: 13px;
            font-weight: 800;
            text-align: center;
            color: #1a1a2e;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /*
         * Las barras se construyen con una tabla de 2 filas:
         * Fila 1 (alineación inferior) = espacio vacío + número
         * Fila 2 = la barra de color
         * Fila 3 = etiqueta + porcentaje
         *
         * Truco DomPDF: usamos celdas de altura fija para simular
         * una gráfica de barras.  La altura del contenedor es fija
         * (220px) y la barra ocupa `barHeight` px desde abajo.
         */
        .chart-outer {
            width: 100%;
            border-collapse: collapse;
        }
        /* fila de valores (superior) */
        .chart-outer td.val-cell {
            vertical-align: bottom;
            text-align: center;
            padding: 0 6px 4px;
            border-bottom: none;
            height: 240px;   /* altura total del área de barras */
        }
        /* fila de etiquetas (inferior) */
        .chart-outer td.lbl-cell {
            text-align: center;
            padding: 8px 4px 0;
            vertical-align: top;
            border-top: 2px solid #cbd5e1;
        }

        /* La barra: div con altura inline, fondo sólido */
        .bar-block {
            display: block;
            width: 80%;
            margin: 0 auto;
            border-radius: 6px 6px 3px 3px;
        }
        /* Número encima de la barra */
        .bar-num {
            display: block;
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 3px;
        }
        /* % en píldora */
        .bar-pct {
            display: inline-block;
            font-size: 9px;
            font-weight: 700;
            color: #ffffff;
            padding: 2px 6px;
            border-radius: 10px;
            margin-bottom: 5px;
        }
        .lbl-range {
            font-size: 10px;
            font-weight: 800;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: block;
        }
        .lbl-count {
            font-size: 9px;
            color: #64748b;
            display: block;
            margin-top: 2px;
        }

        /* ── LEYENDA ── */
        .legend-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #f1f5f9;
            padding: 6px 10px;
        }
        .legend-table td {
            padding: 5px 10px;
            font-size: 9px;
            font-weight: 600;
            white-space: nowrap;
        }
        .legend-dot {
            display: inline-block;
            width: 18px;
            height: 10px;
            border-radius: 3px;
            vertical-align: middle;
            margin-right: 5px;
        }

        /* ── DETALLE DE CURSOS ── */
        .details-title {
            font-size: 13px;
            font-weight: 800;
            color: #1a1a2e;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .rango-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
            border: 1px solid #e2e8f0;
        }

        .rango-header {
            padding: 9px 14px;
            border-left: 5px solid #000;
        }
        .rango-title {
            font-size: 11px;
            font-weight: 800;
            display: inline;
        }
        .rango-count {
            font-size: 9px;
            font-weight: 600;
            display: inline;
            margin-left: 8px;
            padding: 2px 8px;
            border-radius: 10px;
            background: rgba(0,0,0,0.1);
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        table.data-table thead tr {
            background: #1e293b;
            color: #ffffff;
        }
        table.data-table thead th {
            padding: 8px 10px;
            text-align: left;
            font-weight: 700;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        table.data-table thead th.center { text-align: center; }
        table.data-table tbody tr { border-bottom: 1px solid #e2e8f0; }
        table.data-table tbody tr:nth-child(even) { background: #f8fafc; }
        table.data-table tbody td { padding: 8px 10px; vertical-align: middle; }
        table.data-table tbody td.center { text-align: center; }

        .curso-nombre { font-weight: 700; font-size: 9.5px; color: #0f172a; }
        .curso-id { font-size: 7.5px; color: #64748b; margin-top: 2px; }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 7.5px;
            font-weight: 700;
        }
        .badge-purple { background: #e9d5ff; color: #6b21a5; }
        .badge-green  { background: #dcfce7; color: #166534; }
        .badge-amber  { background: #fef3c7; color: #92400e; }
        .badge-gray   { background: #f1f5f9; color: #475569; }

        /* ── NOTA AZUL ── */
        .nota-azul {
            margin-top: 15px;
            padding: 12px 15px;
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            font-size: 8.5px;
            color: #1e3a8a;
        }

        /* ── FOOTER ── */
        .footer {
            margin-top: 25px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            line-height: 1.8;
        }
    </style>
</head>
<body>
<div class="main-container">

    {{-- ── HEADER ── --}}
    <div class="header">
        <div class="header-left">
            <h1>REPORTE DE ACCESOS DOCENTES
                <small>Distribucion de porcentajes de acceso (valores reales &gt;100%)</small>
            </h1>
        </div>
        <div class="header-right">
            <div class="meta">
                Fecha: {{ now()->format('d/m/Y H:i:s') }}<br>
                TecNM Campus Aguascalientes
            </div>
        </div>
    </div>

    {{-- ── BANNER ── --}}
    <div class="summary">
        TOTAL DE CURSOS ANALIZADOS: <span>{{ $totalCursos }}</span> cursos con creditos validos
    </div>

    <div class="body-pad">

        {{-- ── TARJETAS STAT ── --}}
        @php
            $colores = ['#dc2626','#f59e0b','#eab308','#10b981','#3b82f6'];
            $i = 0;
        @endphp
        <table class="stats-table">
            <tr>
                @foreach($rangos as $rango)
                @php $color = $colores[$i++]; @endphp
                <td>
                    <span class="stat-stripe" style="background: {{ $color }};"></span>
                    <span class="stat-number" style="color: {{ $color }};">{{ $rango['count'] }}</span>
                    <span class="stat-label">{{ $rango['label'] }}</span>
                </td>
                @endforeach
            </tr>
        </table>

        {{-- ── GRÁFICA DE BARRAS ── --}}
        <div class="chart-section">
            <div class="chart-title">DISTRIBUCION DE CURSOS POR RANGO DE ACCESO</div>

            @php
                $maxCount = max(array_column($rangos, 'count'));
                $maxCount = $maxCount > 0 ? $maxCount : 1;
                $maxBarH  = 200; // px disponibles para la barra mas alta
                $colores2 = ['#dc2626','#f59e0b','#eab308','#10b981','#3b82f6'];
                $j = 0;
            @endphp

            <table class="chart-outer">
                {{-- Fila 1: área de barras --}}
                <tr>
                    @foreach($rangos as $rango)
                    @php
                        $color   = $colores2[$j++];
                        $barH    = $rango['count'] > 0
                                    ? max(12, round(($rango['count'] / $maxCount) * $maxBarH))
                                    : 0;
                        $pctTotal = $totalCursos > 0
                                    ? round(($rango['count'] / $totalCursos) * 100, 1)
                                    : 0;
                    @endphp
                    <td class="val-cell">
                        @if($rango['count'] > 0)
                            <span class="bar-num" style="color: {{ $color }};">{{ $rango['count'] }}</span>
                            <span class="bar-pct" style="background: {{ $color }};">{{ $pctTotal }}%</span>
                        @endif
                        <span class="bar-block"
                              style="height: {{ $barH }}px; background: {{ $color }};">
                        </span>
                    </td>
                    @endforeach
                </tr>

                {{-- Fila 2: etiquetas --}}
                <tr>
                    @php $k = 0; @endphp
                    @foreach($rangos as $rango)
                    @php $color = $colores2[$k++]; @endphp
                    <td class="lbl-cell">
                        <span class="lbl-range" style="color: {{ $color }};">{{ $rango['label'] }}</span>
                        <span class="lbl-count">{{ $rango['count'] }} curso(s)</span>
                    </td>
                    @endforeach
                </tr>
            </table>

            {{-- Leyenda --}}
            <table class="legend-table">
                <tr>
                    @php $l = 0; @endphp
                    @foreach($rangos as $rango)
                    @php $color = $colores2[$l++]; @endphp
                    <td>
                        <span class="legend-dot" style="background: {{ $color }};"></span>
                        {{ $rango['label'] }} ({{ $rango['count'] }})
                    </td>
                    @endforeach
                </tr>
            </table>
        </div>

        {{-- ── DETALLE DE CURSOS ── --}}
        <div class="details-title">DETALLE DE CURSOS POR RANGO</div>

        @php $m = 0; @endphp
        @foreach($rangos as $key => $rango)
        @php $color = $colores2[$m++]; @endphp
        @if(count($rango['cursos']) > 0)
        <div class="rango-section">

            <div class="rango-header"
                 style="background: {{ $color }}15; border-left-color: {{ $color }};">
                <span class="rango-title" style="color: {{ $color }};">{{ $rango['label'] }}</span>
                <span class="rango-count">{{ count($rango['cursos']) }} cursos</span>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th width="36%">Curso</th>
                        <th width="28%">Docente</th>
                        <th width="14%" class="center">Categoria</th>
                        <th width="22%" class="center">% Accesos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rango['cursos'] as $curso)
                    @php
                        $pctReal   = (float) $curso['porcentaje_accesos'];
                        $sufijo    = $pctReal > 100 ? '% ++' : '%';
                        $displayPct = number_format($pctReal, 1) . $sufijo;

                        $catColors  = [444 => 'badge-purple', 445 => 'badge-green', 450 => 'badge-amber'];
                        $badgeClass = $catColors[$curso['category']] ?? 'badge-gray';
                    @endphp
                    <tr>
                        <td>
                            <div class="curso-nombre">{{ $curso['nombre_curso'] }}</div>
                            <div class="curso-id">
                                ID: {{ $curso['courseid'] }} |
                                Sem: {{ $curso['semanas_habiles'] }} |
                                Cred: {{ $curso['creditos'] }}
                            </div>
                        </td>
                        <td>{{ $curso['docente_nombre'] ?? 'Sin asignar' }}</td>
                        <td class="center">
                            <span class="badge {{ $badgeClass }}">Cat. {{ $curso['category'] }}</span>
                        </td>
                        <td class="center">
                            <strong style="font-size: 11px; color: {{ $color }};">{{ $displayPct }}</strong><br>
                            <small style="font-size: 7px; color: #64748b;">
                                {{ number_format($curso['ingresos_reales'], 0) }} /
                                {{ number_format($curso['ingresos_esperados'], 0) }}
                            </small>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        @endforeach

        @if($rangos['mas_100']['count'] > 0)
        <div class="nota-azul">
            <strong>NOTA:</strong>
            Los cursos con mas del 100% de acceso indican que los docentes han ingresado
            mas veces de las esperadas, superando el minimo requerido.
        </div>
        @endif

    </div>{{-- /body-pad --}}

    <div class="footer">
        Sistema de Monitoreo EaD &middot; TecNM Campus Aguascalientes<br>
        Reporte generado el {{ now()->format('d/m/Y \a \l\a\s H:i:s') }} &nbsp;|&nbsp;
        Valores reales de acceso (sin normalizar)
    </div>

</div>
</body>
</html>