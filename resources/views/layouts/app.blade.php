<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — Moodle Analytics</title>

    {{-- Estilos base --}}

    {{-- Livewire --}}
    @livewireStyles

    {{-- ApexCharts vía CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f4f6f9;
            margin: 0;
            color: #1a1a2e;
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: 240px;
            height: 100vh;
            background: #1a1a2e;
            display: flex;
            flex-direction: column;
            padding: 0;
            z-index: 100;
        }

        .sidebar-logo {
            padding: 1.5rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-logo h1 {
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            margin: 0 0 2px;
        }

        .sidebar-logo span {
            font-size: 11px;
            color: rgba(255,255,255,0.4);
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto;
        }

        .nav-section {
            padding: 0.5rem 1.25rem 0.25rem;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.3);
            margin-top: 0.5rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.6rem 1.25rem;
            font-size: 13px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.15s;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.05);
            color: #fff;
        }

        .nav-item.active {
            background: rgba(99,102,241,0.15);
            color: #fff;
            border-left-color: #6366f1;
        }

        .nav-item .icon {
            width: 16px;
            text-align: center;
            font-size: 14px;
        }

        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        /* ── Main content ── */
        .main {
            margin-left: 240px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Topbar ── */
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e8ecf0;
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ── Botones ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.45rem 0.9rem;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.15s;
        }

        .btn-primary {
            background: #6366f1;
            color: #fff;
        }

        .btn-primary:hover { background: #4f46e5; }

        .btn-ghost {
            background: transparent;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .btn-ghost:hover { background: #f8fafc; }

        .btn-danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-danger:hover { background: #fecaca; }

        /* ── Content ── */
        .content {
            flex: 1;
            padding: 1.5rem;
        }

        /* ── Cards ── */
        .card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e8ecf0;
            padding: 1.25rem;
        }

        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a2e;
            margin: 0 0 1rem;
        }

        /* ── Metric cards ── */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 1.5rem;
        }

        .metric-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e8ecf0;
            padding: 1.1rem;
        }

        .metric-label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 6px;
        }

        .metric-value {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1;
        }

        .metric-sub {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .metric-card.purple .metric-value { color: #6366f1; }
        .metric-card.green .metric-value  { color: #10b981; }
        .metric-card.red .metric-value    { color: #ef4444; }
        .metric-card.amber .metric-value  { color: #f59e0b; }

        /* ── Tablas ── */
        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead th {
            text-align: left;
            padding: 0.6rem 0.85rem;
            background: #f8fafc;
            color: #64748b;
            font-weight: 500;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #e8ecf0;
        }

        tbody td {
            padding: 0.75rem 0.85rem;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        tbody tr:last-child td { border: none; }
        tbody tr:hover td { background: #f8fafc; }

        /* ── Badges ── */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-red    { background: #fee2e2; color: #dc2626; }
        .badge-amber  { background: #fef3c7; color: #b45309; }
        .badge-purple { background: #ede9fe; color: #7c3aed; }
        .badge-gray   { background: #f1f5f9; color: #475569; }

        /* ── Alertas ── */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 1rem;
        }

        .alert-success { background: #dcfce7; color: #15803d; }
        .alert-error   { background: #fee2e2; color: #dc2626; }

        /* ── Formularios ── */
        .form-group { margin-bottom: 1rem; }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }

        .form-input {
            width: 100%;
            padding: 0.55rem 0.85rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            color: #1a1a2e;
            background: #fff;
            transition: border-color 0.15s;
        }

        .form-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }

        .form-error {
            font-size: 12px;
            color: #dc2626;
            margin-top: 4px;
        }
    </style>

    @stack('styles')
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo">
            <h1>Moodle Analytics</h1>
            <span>Área de Educación a Distancia</span>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">General</div>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="icon">📊</span> Dashboard
            </a>

            <div class="nav-section">Reportes</div>
            <a href="{{ route('docentes.index') }}" class="nav-item {{ request()->routeIs('docentes.*') ? 'active' : '' }}">
                <span class="icon">👨‍🏫</span> Docentes
            </a>
            <a href="{{ route('calificaciones.index') }}" class="nav-item {{ request()->routeIs('calificaciones.*') ? 'active' : '' }}">
                <span class="icon">📚</span> Calificaciones
            </a>
           <!-- <a href="{{ route('alumnos.riesgo') }}" class="nav-item {{ request()->routeIs('alumnos.*') ? 'active' : '' }}">
                <span class="icon">⚠️</span> Alumnos en riesgo
            </a> -->

            <div class="nav-section">Sistema</div>
            <form method="POST" action="{{ route('cache.limpiar') }}">
                @csrf
                <button type="submit" class="nav-item" style="width:100%;background:none;border:none;cursor:pointer;text-align:left;">
                    <span class="icon">🔄</span> Limpiar caché
                </button>
            </form>
            <div class="nav-section">Funciones</div>
            <a href="{{ route('limpieza.index') }}" class="nav-item {{ request()->routeIs('limpieza.*') ? 'active' : '' }}">
                <span class="icon">⚠️</span> Eliminar etiquetas
            </a>

        </nav>

        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost" style="width:100%;justify-content:center;">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-title">@yield('title', 'Dashboard')</div>
            <div class="topbar-actions">
                @yield('actions')
            </div>
        </div>

        <div class="content">
            {{-- Mensajes flash --}}
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </div>

    @livewireScripts

    @stack('scripts')
</body>
</html>
