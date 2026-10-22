@extends('layouts.app')
@section('title', $def['titulo'])
@section('content')
@php $visibles = collect($def['campos'])->reject(fn ($c) => !empty($c['oculto'])); @endphp
<div class="page-head">
    <div><h1>{{ $def['titulo'] }}</h1><p>{{ $def['rf'] }} · {{ $registros->total() }} registros</p></div>
    <div class="actions">
        @if ($def['clave'] === 'accesos')<a class="btn btn-ghost" href="{{ route('seguridad.index') }}">Asignar guardias</a>@endif
        <a class="btn" href="{{ route('catalogos.create', $def['clave']) }}">Nuevo {{ $def['singular'] }}</a>
    </div>
</div>

<div class="card">
    <form class="filters" method="GET">
        <div class="field"><label for="q">Buscar</label><input id="q" class="input" name="q" value="{{ $buscar }}" placeholder="Texto…"></div>
        <button class="btn btn-ghost">Filtrar</button>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr>
            @foreach ($visibles as $c)<th>{{ $c[0] }}</th>@endforeach
            @if ($def['clave'] === 'accesos')<th>Guardias</th>@endif
            <th></th>
        </tr></thead>
        <tbody>
        @forelse ($registros as $r)
            <tr>
                @foreach ($visibles as $campo => $c)
                    <td>
                        @if ($c[1] === 'enum' && $campo === 'estado')
                            <x-estado :valor="$r->$campo" />
                        @elseif ($c[1] === 'select')
                            {{ data_get($r, $c['mostrar']) ?? '—' }}
                        @elseif ($c[1] === 'date')
                            {{ $r->$campo?->format('d/m/Y') ?? '—' }}
                        @else
                            {{ $r->$campo ?? '—' }}
                        @endif
                    </td>
                @endforeach
                @if ($def['clave'] === 'accesos')
                    <td>@if($r->guardias->isEmpty())<span class="badge bad">Sin guardia (RN-007)</span>@else{{ $r->guardias->pluck('nombre_completo')->join(', ') }}@endif</td>
                @endif
                <td class="num"><a class="btn btn-ghost btn-sm" href="{{ route('catalogos.edit', [$def['clave'], $r->getKey()]) }}">Editar</a></td>
            </tr>
        @empty
            <tr><td colspan="{{ $visibles->count() + 2 }}" class="empty">No hay registros.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $registros->links('partials.paginacion') }}
</div>
@endsection
