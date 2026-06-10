<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de seguimiento de calificaciones</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif; 
            font-size: 10px; 
            color: #1a1a2e; 
            padding: 20px; 
            line-height: 1.4;
        }

        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start;
            margin-bottom: 16px; 
            padding-bottom: 12px; 
            border-bottom: 2px solid #1a1a2e; 
        }
        
        .header h1 { 
            font-size: 16px; 
            font-weight: 700; 
            color: #1a1a2e; 
        }
        
        .header .meta { 
            font-size: 9px; 
            color: #64748b; 
            text-align: right; 
        }

        .summary { 
            margin-bottom: 12px; 
            padding: 8px 12px; 
            background: #f1f5f9;
            border-left: 3px solid #1a1a2e; 
            border-radius: 4px; 
        }
        
        .summary span { 
            font-weight: 700; 
        }

        /* NUEVO: stats en línea horizontal compacta */
        .stats-row {
            display: flex;
            flex-direction: row;
            gap: 8px;
            margin-bottom: 15px;
            padding: 8px 12px;
            background: #f8fafc;
            border-radius: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .stat-item-row {
            display: inline-flex;
            align-items: baseline;
            gap: 6px;
            background: white;
            padding: 4px 12px;
            border-radius: 20px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .stat-number-row {
            font-size: 18px;
            font-weight: 800;
            line-height: 1.2;
        }
        
        .stat-label-row {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .stat-divider {
            color: #cbd5e1;
            font-size: 14px;
            font-weight: 300;
        }

        .legend { 
            display: flex; 
            gap: 16px; 
            margin-bottom: 10px; 
            font-size: 8px; 
        }
        
        .legend-item { 
            display: flex; 
            align-items: center; 
            gap: 4px; 
        }
        
        .legend-dot { 
            width: 10px; 
            height: 10px; 
            border-radius: 2px; 
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 4px; 
        }
        
        thead tr { 
            background: #1a1a2e; 
            color: #fff; 
        }
        
        thead th { 
            padding: 6px 6px; 
            text-align: left; 
            font-size: 8px; 
            font-weight: 600;
            text-transform: uppercase; 
            letter-spacing: .04em; 
        }
        
        thead th.center { 
            text-align: center; 
        }

        .row-error    { background: #fff1f1; border-bottom: 1px solid #fecaca; }
        .row-warning { background: #f0fdf4; border-bottom: 1px solid #bbf7d0; }
        .row-success  { background: #f0fdf4; border-bottom: 1px solid #bbf7d0; }

        tbody td { 
            padding: 6px 6px; 
            vertical-align: top; 
            font-size: 8px;
        }
        
        tbody td.center { 
            text-align: center; 
        }

        .curso-nombre { 
            font-weight: 700; 
            font-size: 9px; 
        }
        
        .profesor-nombre {
            font-weight: 600;
            font-size: 8px;
            color: #1e40af;
            margin-top: 3px;
        }
        
        .categoria-texto {
            font-size: 7px;
            color: #6b7280;
            margin-top: 2px;
            font-style: italic;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: 600;
        }
        
        .badge-error { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #dcfce7; color: #166534; }
        .badge-success { background: #dcfce7; color: #166534; }

        .footer { 
            margin-top: 14px; 
            font-size: 7px; 
            color: #94a3b8; 
            text-align: right;
            border-top: 1px solid #e2e8f0; 
            padding-top: 6px; 
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h1>Reporte de Seguimiento de Calificaciones</h1>
            <div style="font-size:8px; color:#64748b; margin-top:3px;">
                Seguimiento de calificaciones de actividades por tema
            </div>
        </div>
        <div class="meta">
            Fecha reporte: {{ $fecha_reporte }}<br>
            TecNM Campus Aguascalientes
        </div>
    </div>



    @if(empty($cursos))
        <p style="text-align:center; color:#94a3b8; margin-top:2rem;">No hay cursos para mostrar.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th width="30%">Curso / Categoría</th>
                    <th width="20%">Profesor(es)</th>
                    <th width="28%">Seguimiento</th>
                    <th width="22%">Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cursos as $curso)
                @php
                    $rowClass = match($curso['estado']) {
                        'error' => 'row-error',
                        'warning' => 'row-warning',
                        default => 'row-success'
                    };
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>
                        <div class="curso-nombre">{{ $curso['curso'] }}</div>
                        <div class="categoria-texto">{{ $curso['categoria'] }}</div>
                    </td>
                    <td>
                        <div class="profesor-nombre">{{ $curso['profesor'] }}</div>
                    </td>
                    <td style="font-size: 8px;">{{ $curso['seguimiento'] }}</td>
                    <td style="font-size: 7px; color:#64748b;">{{ $curso['observaciones'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Sistema de Monitoreo EaD · TecNM Campus Aguascalientes · Reporte generado automáticamente
    </div>

</body>
</html>