<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #0f172a;
            background: #f0f4f8;
            padding: 20px;
        }
        .wrap { background: #ffffff; border: 1px solid #dde3ee; }

        .body { padding: 20px 24px; }

        .det-title {
            font-size: 12px; font-weight: 800; color: #0f172a;
            text-transform: uppercase; letter-spacing: .5px;
            padding-bottom: 8px; border-bottom: 2px solid #e2e8f0; margin-bottom: 16px;
        }
        .sec { margin-bottom: 18px; border: 1px solid #e2e8f0; page-break-inside: avoid; }
        .sec-hdr { padding: 9px 14px; border-left: 4px solid #000; }
        .sec-title { font-size: 10.5px; font-weight: 800; display: inline; }
        .sec-count { font-size: 8.5px; font-weight: 600; display: inline; margin-left: 8px; padding: 2px 8px; border-radius: 8px; background: rgba(0,0,0,0.08); }

        table.dt { width: 100%; border-collapse: collapse; font-size: 8.5px; }
        table.dt thead tr { background: #1e293b; color: #f8fafc; }
        table.dt thead th { padding: 8px 10px; text-align: left; font-weight: 700; font-size: 8px; text-transform: uppercase; letter-spacing: .8px; }
        table.dt thead th.c { text-align: center; }
        table.dt tbody tr { border-bottom: 1px solid #e2e8f0; }
        table.dt tbody tr:nth-child(even) { background: #f8fafc; }
        table.dt tbody td { padding: 8px 10px; vertical-align: middle; }
        table.dt tbody td.c { text-align: center; }

        .footer { border-top: 1px solid #e2e8f0; padding: 10px 24px; text-align: center; font-size: 8px; color: #94a3b8; line-height: 1.9; }
    </style>
</head>
<body>
<div class="wrap">

    <div class="body">
        <div class="det-title">DETALLE DE CURSOS POR ESTADO</div>

        @foreach($estadosList as $e)
        @php
            $cursos = $estadosCursos['detalle'][$e['key']]['cursos'] ?? [];
            $c = $e['color'];
        @endphp
        @if(count($cursos) > 0)
        <div class="sec">
            <div class="sec-hdr" style="background:{{ $c }}12; border-left-color:{{ $c }};">
                <span class="sec-title" style="color:{{ $c }};">{{ $e['lbl'] }}</span>
                <span class="sec-count">{{ count($cursos) }} cursos</span>
            </div>
            <table class="dt">
                <thead>
                    <tr>
                        <th width="30%">Curso</th>
                        <th width="20%">Categoria</th>
                        <th width="22%">Docente</th>
                        <th width="9%" class="c">T. Corte</th>
                        <th width="9%" class="c">Debidos</th>
                        <th width="9%" class="c">Calif.</th>
                        @if($e['key'] !== 'calificado_completo')
                        <th width="9%" class="c">Pend.</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($cursos as $curso)
                    <tr>
                        <td>{{ $curso['curso'] }}</td>
                        <td>{{ $curso['categoryname'] }}</td>
                        <td>{{ $curso['profesor'] }}</td>
                        <td class="c">T{{ $curso['tema_corte'] }}</td>
                        <td class="c">{{ $curso['total_temas_debidos'] }}</td>
                        <td class="c" style="color:#059669; font-weight:700;">{{ $curso['temas_calificados_ok'] }}</td>
                        @if($e['key'] !== 'calificado_completo')
                        <td class="c" style="color:{{ $c }}; font-weight:700;">{{ $curso['temas_con_problema'] }}</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        @endforeach
    </div>

    <div class="footer">
        Sistema de Monitoreo EaD &middot; TecNM Campus Aguascalientes<br>
        Reporte basado en temas que debian estar calificados hasta el tema de corte
    </div>

</div>
</body>
</html>