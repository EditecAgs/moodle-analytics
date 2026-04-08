@extends('layouts.app')

@section('title', 'Actualización de secciones')

@section('content')

<div class="card">
    <div class="card-title">Actualizar sección 0</div>

<form method="POST" action="{{ route('secciones.actualizar') }}">
    @csrf

    <div>
        <label>Nombre de la sección</label>
        <input type="text" name="nombre" required>
    </div>

    <div style="margin-top:10px;">
        <label>Número de sección</label>
        <input type="number" name="seccion" value="0" min="0" required>
    </div>

    <button type="submit">Actualizar</button>
</form>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@endsection