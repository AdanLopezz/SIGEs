<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\FiltrarUsuariosRequest;
use App\Http\Requests\Administracion\QuitarAccesoRequest;
use App\Http\Requests\Administracion\UsuarioRequest;
use App\Models\OrgArea;
use App\Models\User;
use App\Support\Auditoria;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function index(FiltrarUsuariosRequest $solicitud)
    {
        $filtros = $solicitud->validated();
        $usuarios = User::with(['persona.personal.area', 'roles'])->visibles()
            ->buscar($filtros['buscar'] ?? null)->conRol($filtros['rol'] ?? null)->acceso($filtros['acceso'] ?? null)
            ->orderBy('name')->orderBy('id')->paginarListado();
        $roles = Role::where('guard_name', 'web')->orderBy('name')->pluck('name');

        return view('administracion.usuarios.index', compact('usuarios', 'roles', 'filtros'));
    }

    public function create()
    {
        return $this->formulario(new User);
    }

    public function store(UsuarioRequest $solicitud)
    {
        Auditoria::motivoPorDefecto('Alta de usuario');
        $usuario = User::crearPersonal($solicitud->validated());

        return redirect()->route('usuarios.acceso', $usuario)
            ->with('status', 'Usuario creado correctamente. Entrega estos datos de acceso a la persona.');
    }

    public function edit(User $usuario)
    {
        $this->protegerSistema($usuario);

        return $this->formulario($usuario);
    }

    public function update(UsuarioRequest $solicitud, User $usuario)
    {
        $this->protegerSistema($usuario);
        Auditoria::motivo($solicitud->validated('motivo'));
        $usuario->actualizarDatos($solicitud->safe()->except('motivo'));

        return redirect()->route('usuarios.index')->with('status', 'Datos del usuario actualizados correctamente.');
    }

    public function quitarAcceso(QuitarAccesoRequest $solicitud, User $usuario)
    {
        $this->protegerSistema($usuario);
        Auditoria::motivo($solicitud->validated('motivo'));
        try {
            $usuario->quitarAcceso();
        } catch (ValidationException $excepcion) {
            return back()->withErrors($excepcion->errors());
        }

        return back()->with('status', 'Se quitó el acceso al usuario. La cuenta y sus datos se conservaron.');
    }

    private function formulario(User $usuario)
    {
        $usuario->loadMissing(['persona.personal', 'roles']);
        $roles = User::rolesAdministrables();
        $areas = OrgArea::orderBy('nombre')->pluck('nombre', 'id');
        $historial = $usuario->exists ? [['per_persona', $usuario->per_persona_id], ['per_personal', $usuario->persona?->personal?->id], ['model_has_roles', $usuario->id]] : [];

        return view('administracion.usuarios.form', compact('usuario', 'roles', 'areas', 'historial'));
    }

    private function protegerSistema(User $usuario): void
    {
        abort_if($usuario->esSistema(), 404);
    }
}
