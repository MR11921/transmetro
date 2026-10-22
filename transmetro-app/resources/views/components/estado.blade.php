@props(['valor'])
@php
    $clase = match ($valor) {
        'ACTIVO', 'ACTIVA', 'VALIDO', 'VALIDADO', 'DISPONIBLE', 'FINALIZADO', 'ATENDIDA', 'CERRADO', 'Cumple' => 'ok',
        'INACTIVO', 'INACTIVA', 'ANULADO', 'RECHAZADO', 'DESCONECTADO', 'CANCELADO', 'CANCELADA', 'CRITICA', 'ALTA', 'No cumple' => 'bad',
        'MANTENIMIENTO', 'PENDIENTE', 'ABIERTO', 'EN_CURSO', 'MEDIA', 'OCUPADO', 'VENCIDO' => 'warn',
        'PROGRAMADO', 'EN_PROCESO', 'UTILIZADO', 'BAJA' => 'info',
        default => '',
    };
@endphp
<span class="badge {{ $clase }}">{{ str_replace('_', ' ', $valor) }}</span>
