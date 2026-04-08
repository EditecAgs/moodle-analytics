<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — Moodle Analytics</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f4f6f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8ecf0;
            padding: 2.5rem;
            width: 100%;
            max-width: 380px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo h1 {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 4px;
        }

        .login-logo p {
            font-size: 13px;
            color: #64748b;
        }

        .form-group { margin-bottom: 1.25rem; }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 0.65rem 0.9rem;
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
            margin-top: 5px;
        }

        .btn-submit {
            width: 100%;
            padding: 0.7rem;
            background: #6366f1;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-submit:hover { background: #4f46e5; }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <h1>Moodle Analytics</h1>
            <p>Área de Educación a Distancia</p>
        </div>

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="password">Contraseña de acceso</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    placeholder="Ingresa tu contraseña"
                    autofocus
                    required
                >
                @error('password')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn-submit">Entrar</button>
        </form>

        <div class="login-footer">
            Acceso restringido al área de EaD
        </div>
    </div>
</body>
</html>
