<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\MetodoPago;
use App\Models\Pasajero;
use App\Models\Ticket;
use App\Services\Auditor;
use App\Services\ControlAcceso;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF-011 tickets y métodos de pago · RF-012 Terminal de Autoservicio (TAT). */
class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = Ticket::with(['metodoPago', 'pasajero'])
            ->when($request->query('estado'), fn ($q, $e) => $q->where('estado', $e))
            ->when($request->query('q'), fn ($q, $c) => $q->where('codigo_ticket', 'like', '%'.strtoupper($c).'%'))
            ->latest('fecha_emision')->paginate(20)->withQueryString();

        return view('tickets.index', compact('tickets'));
    }

    /** Pantalla del TAT: el pasajero adquiere su ticket. */
    public function tat()
    {
        return view('tickets.tat', [
            'metodos' => MetodoPago::where('estado', 'ACTIVO')->orderBy('nombre')->get(),
            'pasajeros' => Pasajero::where('estado', 'ACTIVO')->orderBy('apellido')->get(),
            'ultimo' => session('ticket') ? Ticket::with('metodoPago')->find(session('ticket')) : null,
        ]);
    }

    public function emitir(Request $request, ControlAcceso $control)
    {
        $datos = $request->validate([
            'id_metodo_pago' => ['required', Rule::exists('metodos_pago', 'id_metodo_pago')],
            'id_pasajero' => ['nullable', Rule::exists('pasajeros', 'id_pasajero')],
            'cantidad' => ['required', 'integer', 'min:1', 'max:10'],
        ], [], ['id_metodo_pago' => 'método de pago']);

        $ultimo = null;
        for ($i = 0; $i < $datos['cantidad']; $i++) {
            $ultimo = $control->emitirTicket((int) $datos['id_metodo_pago'], $datos['id_pasajero'] ?? null);
        }

        $mensaje = $datos['cantidad'] > 1 ? "Se emitieron {$datos['cantidad']} tickets." : "Ticket {$ultimo->codigo_ticket} emitido.";

        return redirect()->route('tat')->with('ok', $mensaje)->with('ticket', $ultimo->id_ticket);
    }

    public function anular(Ticket $ticket)
    {
        if ($ticket->estado !== 'VALIDO') {
            throw new ReglaNegocioException("Solo se pueden anular tickets válidos (estado actual: {$ticket->estado}).");
        }

        $ticket->update(['estado' => 'ANULADO']);
        Auditor::registrar('Tickets', 'ANULAR', 'tickets', $ticket->id_ticket, $ticket->codigo_ticket);

        return back()->with('ok', "Ticket {$ticket->codigo_ticket} anulado.");
    }
}
