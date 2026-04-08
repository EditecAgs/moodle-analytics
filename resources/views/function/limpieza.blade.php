@extends('layouts.app')

@section('title', 'Limpieza de Módulos')

@section('actions')
    <span style="font-size:12px; color:#94a3b8;">
        Labels RECURSOS y ACTIVIDADES encontrados
    </span>
@endsection

@section('content')

    {{-- Alertas --}}
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #86efac; color:#15803d; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:13px;">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; border:1px solid #fca5a5; color:#dc2626; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:13px;">
            ❌ {{ session('error') }}
        </div>
    @endif

    {{-- KPI --}}
    <div class="metric-grid" style="grid-template-columns: repeat(2, 1fr);">
        <div class="metric-card amber">
            <div class="metric-label">Cursos con labels</div>
            <div class="metric-value">{{ count($cursosCmids) }}</div>
            <div class="metric-sub">cursos afectados</div>
        </div>
        <div class="metric-card red">
            <div class="metric-label">Total módulos</div>
            <div class="metric-value">{{ $totalModulos }}</div>
            <div class="metric-sub">labels RECURSOS / ACTIVIDADES</div>
        </div>
    </div>

    {{-- Advertencia --}}
    <div style="background:#fef3c7; border:1px solid #fcd34d; color:#92400e; padding:0.85rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:13px;">
        ⚠️ <strong>Esta acción es permanente.</strong> Los módulos eliminados no se pueden recuperar desde el dashboard. Asegúrate de seleccionar solo los que quieres borrar.
    </div>

    {{-- Tabla con form --}}
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <div class="card-title" style="margin:0;">Labels encontrados por curso</div>
        </div>

        @if(empty($cursosCmids))
            <div style="text-align:center; padding:2rem; color:#94a3b8; font-size:13px;">
                No se encontraron labels RECURSOS o ACTIVIDADES en ningún curso. ✅
            </div>
        @else
            <form method="POST" action="{{ route('limpieza.eliminar') }}"
                  onsubmit="return confirm('¿Estás seguro? Esta acción es permanente en Moodle.')">
                @csrf

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:40px;">
                                    <input type="checkbox" id="selectAll"
                                           title="Seleccionar todos"
                                           onclick="toggleAll(this)">
                                </th>
                                <th>Curso</th>
                                <th>Categoría</th>
                                <th style="text-align:center;">Labels encontrados</th>
                                <th style="text-align:center;">CMIDs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cursosCmids as $curso)
                                                        <tr>
                            <td>
                                <div style="display:flex; flex-direction:column; gap:5px;">
                                    @foreach($curso['labels'] as $label)
                                        <label style="display:flex; align-items:center; gap:6px; cursor:pointer; white-space:nowrap;">
                                            <input type="checkbox"
                                                name="cmids[]"
                                                value="{{ $label['cmid'] }}"
                                                class="cmid-check">
                                            <span style="font-family:monospace; background:#f1f5f9; padding:2px 6px;
                                                        border-radius:4px; font-size:11px; color:#64748b; border:1px solid #e2e8f0;">
                                                {{ $label['cmid'] }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </td>
                                <td>
                                    <div style="font-weight:500; color:#1a1a2e;">
                                        {{ $curso['nombre'] }}
                                    </div>
                                    <div style="font-size:11px; color:#94a3b8;">
                                        {{ $curso['corto'] }}
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:12px; color:#64748b;">
                                        {{ $curso['nombreCategoria'] }}
                                    </div>
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge badge-amber">{{ $curso['total'] }}</span>
                                    <button type="button"
                                            onclick="verLabels({{ json_encode($curso['labels']) }}, '{{ addslashes($curso['nombre']) }}')"
                                            style="background:none; border:none; cursor:pointer; color:#64748b; margin-left:4px; padding:0; vertical-align:middle;"
                                            title="Ver etiquetas">
                                        👁️
                                    </button>
                                </td>
                                <td style="text-align:center; font-size:11px; color:#94a3b8;">
                                    {{ implode(', ', $curso['cmids']) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="display:flex; justify-content:flex-end; margin-top:1rem;">
                    <button type="submit"
                            style="background:#dc2626; color:#fff; border:none; padding:0.5rem 1.25rem;
                                   border-radius:6px; font-size:13px; cursor:pointer; font-weight:500;">
                        🗑️ Eliminar seleccionados
                    </button>
                </div>
            </form>
        @endif
    </div>

    {{-- Modal --}}
    <div id="labelModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45);
         z-index:999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:10px; padding:1.5rem; max-width:640px; width:90%;
                    max-height:80vh; overflow-y:auto; position:relative; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
            <button onclick="cerrarModal()"
                    style="position:absolute; top:0.75rem; right:1rem; background:none;
                           border:none; font-size:18px; cursor:pointer; color:#94a3b8; line-height:1;">✕</button>
            <div id="modalTitulo"
                 style="font-weight:600; font-size:14px; margin-bottom:1rem; color:#1a1a2e; padding-right:1.5rem;">
            </div>
            <div id="modalContenido"></div>
        </div>
    </div>

    <script>
        function toggleAll(source) {
            document.querySelectorAll('.cmid-check').forEach(cb => cb.checked = source.checked);
        }

        function verLabels(labels, curso) {
            document.getElementById('modalTitulo').textContent = curso;

            const contenido = labels.map(l => `
                <div style="border:1px solid #e2e8f0; border-radius:8px; padding:0.75rem; margin-bottom:0.75rem;">
                    <div style="font-size:11px; color:#94a3b8; margin-bottom:4px;">CMID: ${l.cmid}</div>
                    <div style="font-size:12px; font-weight:600; color:#475569; margin-bottom:6px;">${l.name}</div>
                    <div style="font-size:12px; color:#64748b;">${l.desc}</div>
                </div>
            `).join('');

            document.getElementById('modalContenido').innerHTML = contenido;
            document.getElementById('labelModal').style.display = 'flex';
        }

        function cerrarModal() {
            document.getElementById('labelModal').style.display = 'none';
        }

        document.getElementById('labelModal').addEventListener('click', function (e) {
            if (e.target === this) cerrarModal();
        });
    </script>

@endsection