<?php

namespace App\Http\Controllers;

use App\Models;
use App\Services\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** RF-023: reportes con filtro de fechas y exportación CSV. */
class ReporteController extends Controller
{
    public const TIPOS = [
        'lineas' => 'Líneas y distancias',
        'estaciones' => 'Estaciones, accesos y guardias',
        'buses' => 'Buses, línea y parqueo',
        'pilotos' => 'Pilotos y recorridos',
        'parqueos' => 'Historial de parqueos',
        'pasajeros' => 'Movimientos de pasajeros por estación',
        'ocupacion' => 'Ocupación por recorrido',
        'tickets' => 'Tickets por método de pago',
        'recorridos' => 'Recorridos',
        'alertas' => 'Alertas',
        'incidentes' => 'Incidentes',
    ];

    public function index()
    {
        return view('reportes.index', ['tipos' => self::TIPOS]);
    }

    public function show(Request $request, string $tipo)
    {
        abort_unless(isset(self::TIPOS[$tipo]), 404);

        $desde = Carbon::parse($request->query('desde', today()->subDays(30)->toDateString()))->startOfDay();
        $hasta = Carbon::parse($request->query('hasta', today()->toDateString()))->endOfDay();

        [$columnas, $filas] = $this->{'reporte'.ucfirst($tipo)}($desde, $hasta);

        if ($request->query('formato') === 'csv') {
            Auditor::registrar('Reportes', 'EXPORTAR_CSV', null, null, self::TIPOS[$tipo]);

            return response()->streamDownload(function () use ($columnas, $filas) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, $columnas);
                foreach ($filas as $fila) {
                    fputcsv($out, $fila);
                }
                fclose($out);
            }, "reporte_{$tipo}_".now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('reportes.show', [
            'tipo' => $tipo, 'titulo' => self::TIPOS[$tipo], 'tipos' => self::TIPOS,
            'columnas' => $columnas, 'filas' => $filas, 'desde' => $desde, 'hasta' => $hasta,
        ]);
    }

    private function reporteLineas(): array
    {
        $filas = Models\Linea::with(['municipalidad', 'estaciones'])->withCount('buses')->orderBy('nombre')->get()->map(fn ($l) => [
            $l->nombre, $l->municipalidad->nombre, $l->estado, $l->estaciones->count(),
            number_format($l->distanciaTotal(), 2), $l->buses_count,
            $l->estaciones->count().' – '.$l->estaciones->count() * 2,
            $l->cumpleCantidadBuses() ? 'Cumple' : 'No cumple',
        ]);

        return [['Línea', 'Municipalidad', 'Estado', 'Estaciones', 'Distancia total (km)', 'Buses', 'Rango RN-004', 'RN-004'], $filas];
    }

    private function reporteEstaciones(): array
    {
        $filas = Models\Estacion::with(['municipalidad', 'lineas', 'accesos.guardias'])->orderBy('nombre')->get()->map(fn ($e) => [
            $e->nombre, $e->municipalidad->nombre, $e->estado, $e->lineas->pluck('nombre')->join(', '),
            $e->accesos->count(), $e->accesos->filter(fn ($a) => $a->guardias->isEmpty())->count(),
            $e->tiene_parqueo === 'S' ? 'Sí' : 'No',
        ]);

        return [['Estación', 'Municipalidad', 'Estado', 'Líneas', 'Accesos', 'Accesos sin guardia', 'Parqueo'], $filas];
    }

    private function reporteBuses(): array
    {
        $filas = Models\Bus::orderBy('numero_interno')->get()->map(fn ($b) => [
            $b->numero_interno, $b->placa, $b->capacidad_maxima, $b->estado,
            $b->lineaActual()?->nombre ?? 'Sin línea', $b->parqueoActual()?->codigo ?? 'SIN PARQUEO',
        ]);

        return [['Nº interno', 'Placa', 'Capacidad', 'Estado', 'Línea actual', 'Parqueo actual'], $filas];
    }

    private function reportePilotos($desde, $hasta): array
    {
        $filas = Models\Piloto::withCount(['recorridos' => fn ($q) => $q->whereBetween('fecha_inicio', [$desde, $hasta])])
            ->orderBy('apellido')->get()->map(fn ($p) => [
                $p->nombre_completo, $p->telefono, $p->correo, $p->direccion, $p->formacion_academica, $p->estado, $p->recorridos_count,
            ]);

        return [['Piloto', 'Teléfono', 'Correo', 'Residencia', 'Formación', 'Estado', 'Recorridos en el período'], $filas];
    }

    private function reporteParqueos($desde, $hasta): array
    {
        $filas = Models\BusParqueo::with(['bus', 'parqueo.estacion'])
            ->where('fecha_inicio', '<=', $hasta)
            ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $desde))
            ->orderByDesc('fecha_inicio')->get()->map(fn ($h) => [
                $h->bus->numero_interno, $h->parqueo->codigo, $h->parqueo->estacion->nombre,
                $h->fecha_inicio->format('d/m/Y H:i'), $h->fecha_fin?->format('d/m/Y H:i') ?? 'Vigente',
            ]);

        return [['Bus', 'Parqueo', 'Estación', 'Desde', 'Hasta'], $filas];
    }

    private function reportePasajeros($desde, $hasta): array
    {
        $filas = Models\MovimientoPasajero::with('estacion')->whereBetween('fecha_hora', [$desde, $hasta])->get()
            ->groupBy('id_estacion')->map(fn ($g) => [
                $g->first()->estacion->nombre,
                $g->where('tipo_movimiento', 'ENTRADA')->where('resultado', 'VALIDADO')->count(),
                $g->where('tipo_movimiento', 'SALIDA')->where('resultado', 'VALIDADO')->count(),
                $g->where('resultado', 'RECHAZADO')->count(),
            ])->sortBy(0)->values();

        return [['Estación', 'Entradas', 'Salidas', 'Rechazos'], $filas];
    }

    private function reporteOcupacion($desde, $hasta): array
    {
        $filas = Models\Recorrido::with(['bus', 'linea', 'controles'])->whereBetween('fecha_inicio', [$desde, $hasta])
            ->latest('fecha_inicio')->get()->map(function ($r) {
                $cap = $r->bus->capacidad_maxima;
                $max = (int) $r->controles->max('ocupacion_calculada');
                $prom = $r->controles->avg('ocupacion_calculada') ?? 0;

                return [
                    $r->id_recorrido, $r->linea->nombre, $r->bus->numero_interno, $cap,
                    $r->controles->sum('pasajeros_abordan'), $max, round($max * 100 / $cap, 1).' %', round($prom * 100 / $cap, 1).' %',
                ];
            });

        return [['Recorrido', 'Línea', 'Bus', 'Capacidad', 'Abordajes', 'Ocupación máx.', '% máx.', '% promedio'], $filas];
    }

    private function reporteTickets($desde, $hasta): array
    {
        $filas = Models\Ticket::with('metodoPago')->whereBetween('fecha_emision', [$desde, $hasta])->get()
            ->groupBy('id_metodo_pago')->map(fn ($g) => [
                $g->first()->metodoPago->nombre, $g->count(),
                $g->where('estado', 'UTILIZADO')->count(), $g->where('estado', 'VALIDO')->count(), $g->where('estado', 'ANULADO')->count(),
                'Q '.number_format($g->where('estado', '!=', 'ANULADO')->sum('valor'), 2),
            ])->values();

        return [['Método de pago', 'Emitidos', 'Utilizados', 'Válidos', 'Anulados', 'Recaudación'], $filas];
    }

    private function reporteRecorridos($desde, $hasta): array
    {
        $filas = Models\Recorrido::with(['bus', 'linea', 'piloto'])->withCount('controles')->whereBetween('fecha_inicio', [$desde, $hasta])
            ->latest('fecha_inicio')->get()->map(fn ($r) => [
                $r->id_recorrido, $r->linea->nombre, $r->bus->numero_interno, $r->piloto->nombre_completo,
                $r->fecha_inicio->format('d/m/Y H:i'), $r->fecha_fin?->format('d/m/Y H:i') ?? '—', $r->estado, $r->controles_count,
            ]);

        return [['ID', 'Línea', 'Bus', 'Piloto', 'Inicio', 'Fin', 'Estado', 'Estaciones visitadas'], $filas];
    }

    private function reporteAlertas($desde, $hasta): array
    {
        $filas = Models\Alerta::with(['bus', 'estacion'])->whereBetween('fecha_hora', [$desde, $hasta])->latest('fecha_hora')->get()->map(fn ($a) => [
            $a->fecha_hora->format('d/m/Y H:i'), $a->tipo_alerta, $a->nivel, $a->bus?->numero_interno ?? '—', $a->estacion?->nombre ?? '—', $a->estado, $a->descripcion,
        ]);

        return [['Fecha', 'Tipo', 'Nivel', 'Bus', 'Estación', 'Estado', 'Descripción'], $filas];
    }

    private function reporteIncidentes($desde, $hasta): array
    {
        $filas = Models\Incidente::with(['bus', 'estacion', 'usuario'])->whereBetween('fecha_hora', [$desde, $hasta])->latest('fecha_hora')->get()->map(fn ($i) => [
            $i->fecha_hora->format('d/m/Y H:i'), $i->tipo_incidente, $i->bus?->numero_interno ?? '—', $i->estacion?->nombre ?? '—', $i->estado, $i->usuario->usuario, $i->descripcion,
        ]);

        return [['Fecha', 'Tipo', 'Bus', 'Estación', 'Estado', 'Registró', 'Descripción'], $filas];
    }
}
