<?php

namespace App\Http\Controllers;

use App\Services\Auditor;
use App\Support\Catalogos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** CRUD genérico para los catálogos definidos en App\Support\Catalogos. */
class CatalogoController extends Controller
{
    public function index(Request $request, string $catalogo)
    {
        $def = $this->autorizar($request, $catalogo);
        $consulta = $def['modelo']::query()->with($def['con'] ?? [])->orderBy($def['orden']);

        if ($buscar = trim((string) $request->query('q'))) {
            $columnas = collect($def['campos'])->filter(fn ($c) => in_array($c[1], ['text', 'email']))->keys();
            $consulta->where(fn ($q) => $columnas->each(fn ($col) => $q->orWhere($col, 'like', "%{$buscar}%")));
        }

        return view('catalogos.index', [
            'def' => $def,
            'registros' => $consulta->paginate(15)->withQueryString(),
            'buscar' => $buscar,
        ]);
    }

    public function create(Request $request, string $catalogo)
    {
        $def = $this->autorizar($request, $catalogo);

        return view('catalogos.form', ['def' => $def, 'registro' => new $def['modelo'], 'opciones' => $this->opciones($def)]);
    }

    public function store(Request $request, string $catalogo)
    {
        $def = $this->autorizar($request, $catalogo);
        $registro = $def['modelo']::create($this->validar($request, $def));

        Auditor::registrar($def['titulo'], 'CREAR', $registro->getTable(), $registro->getKey(), json_encode($registro->getAttributes(), JSON_UNESCAPED_UNICODE));

        return redirect()->route('catalogos.index', $catalogo)->with('ok', ucfirst($def['singular']).' registrado correctamente.');
    }

    public function edit(Request $request, string $catalogo, int $id)
    {
        $def = $this->autorizar($request, $catalogo);

        return view('catalogos.form', ['def' => $def, 'registro' => $def['modelo']::findOrFail($id), 'opciones' => $this->opciones($def)]);
    }

    public function update(Request $request, string $catalogo, int $id)
    {
        $def = $this->autorizar($request, $catalogo);
        $registro = $def['modelo']::findOrFail($id);
        $registro->update($this->validar($request, $def, $registro));

        Auditor::registrar($def['titulo'], 'MODIFICAR', $registro->getTable(), $registro->getKey(), json_encode($registro->getChanges(), JSON_UNESCAPED_UNICODE));

        return redirect()->route('catalogos.index', $catalogo)->with('ok', ucfirst($def['singular']).' actualizado correctamente.');
    }

    private function autorizar(Request $request, string $catalogo): array
    {
        $def = Catalogos::obtener($catalogo);
        abort_unless($request->user()->puede($catalogo), 403, 'No tiene permisos para acceder a este módulo.');

        return $def;
    }

    private function validar(Request $request, array $def, $registro = null): array
    {
        $modelo = new $def['modelo'];
        $reglas = [];

        foreach ($def['campos'] as $campo => $c) {
            $regla = explode('|', $c[2]);

            if (! empty($c['unico'])) {
                $regla[] = Rule::unique($modelo->getTable(), $campo)->ignore($registro?->getKey(), $modelo->getKeyName());
            }
            if ($c[1] === 'enum') {
                $regla[] = Rule::in($c['opciones']);
            }
            if ($c[1] === 'select') {
                [$fuente] = $c['fuente'];
                $f = new $fuente;
                $regla[] = Rule::exists($f->getTable(), $f->getKeyName());
            }

            $reglas[$campo] = $regla;
        }

        $datos = $request->validate($reglas, [], collect($def['campos'])->map(fn ($c) => $c[0])->all());

        // Restricción XOR (p. ej. un dispositivo está en una estación o en un bus, no en ambos).
        if ($unoDe = $def['uno_de'] ?? null) {
            $llenos = collect($unoDe)->filter(fn ($c) => filled($datos[$c] ?? null))->count();
            if ($llenos !== 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $unoDe[0] => 'Debe indicar exactamente una ubicación: '.collect($unoDe)->map(fn ($c) => $def['campos'][$c][0])->join(' o ').'.',
                ]);
            }
        }

        return $datos;
    }

    private function opciones(array $def): array
    {
        return collect($def['campos'])
            ->filter(fn ($c) => $c[1] === 'select')
            ->map(fn ($c) => $c['fuente'][0]::orderBy($c['fuente'][1])->pluck($c['fuente'][1], (new $c['fuente'][0])->getKeyName()))
            ->all();
    }
}
