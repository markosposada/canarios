<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

use App\Http\Controllers\TaxiController;
use App\Http\Controllers\ConductorController;
use App\Http\Controllers\AsignaServicioController;
use App\Http\Controllers\BarrioController;

use App\Http\Controllers\ConductorServiciosController;
use App\Http\Controllers\Conductor\ServiciosAsignadosController;
use App\Http\Controllers\Conductor\PushController;
use App\Http\Controllers\Conductor\NotificacionesController;
use App\Http\Controllers\Conductor\EstadoConductorController;
use App\Http\Controllers\Conductor\MovilesController;
use App\Http\Controllers\Conductor\PerfilController;
use App\Http\Controllers\Conductor\TaxiConductorController;
use App\Http\Controllers\Conductor\FacturacionControllerConductor;

use App\Http\Controllers\Operadora\SancionesController;
use App\Http\Controllers\Operadora\FacturacionController;
use App\Http\Controllers\Operadora\RecaudoController;
use App\Http\Controllers\Operadora\RecaudoHistorialController;
use App\Http\Controllers\Operadora\EstadoConductorControllerOperadora;

use App\Http\Controllers\TaxistaController;

use App\Http\Middleware\RolMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

/* =========================
| HOME
========================= */
Route::get('/', function () {
    if (auth()->check()) {
        $rol = strtolower(trim(auth()->user()->rol ?? ''));

        return match ($rol) {
            'administrador', 'operadora' => redirect('/dashboard'),
            'conductor' => redirect('/modulo-conductor'),
            'propietario taxi' => redirect('/modulo-propietario'),
            default => redirect('/login'),
        };
    }

    return redirect('/login');
})->name('home');

/* =========================
| AUTH: Login / Logout
========================= */
Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->middleware('guest')
    ->name('login');

Route::post('/login', [LoginController::class, 'login'])
    ->middleware('guest')
    ->name('login.post');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Redirección antigua
Route::get('/index', fn () => redirect('/login'));

/* =========================
| AUTH: Registro / Password (placeholder)
========================= */
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('forgot-password', function () {
    return 'Aquí va el formulario para recuperar la contraseña.';
})->name('password.request');

/* =========================
| RUTAS PÚBLICAS (sin login)
========================= */

// Consulta por token (pública con throttle)
Route::get('/servicios/consulta/buscar', [AsignaServicioController::class, 'buscarPorToken'])
    ->middleware('throttle:30,1')
    ->name('servicios.consulta.buscar');

// Registro taxistas (público)
Route::get('/taxistas/crear', [TaxistaController::class, 'create'])->name('taxistas.create');
Route::post('/taxistas', [TaxistaController::class, 'store'])->name('taxistas.store');
Route::get('/taxistas/guardado', fn () => view('taxistas.success'))->name('taxistas.success');

// Crear taxi (si esto debe ser SOLO operadora, muévelo al grupo de operadora)
Route::get('/taxis/crear', [TaxiController::class, 'create'])->name('taxis.create');
Route::post('/taxis', [TaxiController::class, 'store'])->name('taxis.store');

// API pública verificar placa (si debe ser operadora, muévelo al grupo de operadora)
Route::get('/api/verificar-placa', function (\Illuminate\Http\Request $request) {
    $existe = DB::table('taxi')
        ->where('ta_placa', strtoupper($request->query('placa')))
        ->exists();

    return response()->json(['exists' => $existe]);
});

/* =========================
| VISTAS PRINCIPALES (por rol)
========================= */

Route::get('/dashboard', fn () => view('dashboard'))
    ->middleware(['auth', RolMiddleware::class . ':operadora,administrador'])
    ->name('dashboard');

Route::get('/modulo-conductor', fn () => view('modulos.conductor'))
    ->middleware(['auth', RolMiddleware::class . ':conductor'])
    ->name('modulo.conductor');

Route::get('/modulo-operadora', fn () => view('modulos.operadora'))
    ->middleware(['auth', RolMiddleware::class . ':operadora,administrador'])
    ->name('modulo.operadora');

Route::get('/modulo-propietario', fn () => view('modulos.propietario'))
    ->middleware(['auth', RolMiddleware::class . ':propietario taxi'])
    ->name('modulo.propietario');

/* =========================
| GRUPO: OPERADORA + ADMIN
========================= */
Route::middleware(['auth', RolMiddleware::class . ':operadora,administrador'])->group(function () {

    // TAXIS
    Route::get('/taxis/editar-fechas', [TaxiController::class, 'editDates'])->name('taxis.editDates');
    Route::post('/taxis/actualizar-fechas', [TaxiController::class, 'updateDates'])->name('taxis.updateDates');
    Route::get('/api/buscar-taxi', [TaxiController::class, 'buscarPorPlaca']);

    Route::get('/taxis/panel', [TaxiController::class, 'panel'])->name('taxis.panel');
    Route::post('/taxis/cambiar-estado', [TaxiController::class, 'cambiarEstado'])->name('taxis.cambiarEstado');

    // CONDUCTORES (panel/editar/licencias/asignar)
    Route::post('/conductores/buscar-cedula', [ConductorController::class, 'buscarPorCedulaParaEditar']);
    Route::get('/conductores/editar-licencia', [ConductorController::class, 'editLicencia'])->name('conductores.editLicencia');
    Route::post('/buscar-conductor', [ConductorController::class, 'buscarPorCedulaParaEditar']);

    Route::post('/conductores/actualizar-licencia', [ConductorController::class, 'actualizarLicencia'])->name('conductores.actualizarLicencia');

    Route::get('/conductores/asignar', [ConductorController::class, 'asignar'])->name('asignar.form');
    Route::get('/conductores/asignar/buscar', [ConductorController::class, 'buscarConductoresAsignar'])
    ->name('conductores.asignar.buscar');

    Route::post('/buscar-datos-conductor', [ConductorController::class, 'buscarDatosConductor']);
    Route::post('/asignar-conductor', [ConductorController::class, 'guardarAsignacion'])->name('asignar.guardar');

    Route::get('/conductores', [ConductorController::class, 'panelConductores'])->name('conductores.panel');
    Route::get('/conductores/buscar-ajax', [ConductorController::class, 'buscarMovilAjax'])->name('conductores.buscarAjax');

    Route::post('/conductores/movil/{id}/estado', [ConductorController::class, 'actualizarEstado'])
        ->name('conductores.actualizarEstado');

    // SERVICIOS (operadora)
    Route::get('/servicios/asignar', [AsignaServicioController::class, 'vista'])->name('servicios.vista');
    Route::get('/servicios/moviles', [AsignaServicioController::class, 'movilesActivos'])->name('servicios.moviles');
    Route::post('/servicios/registrar', [AsignaServicioController::class, 'registrar'])->name('servicios.registrar');

    Route::get('/servicios/consulta', [AsignaServicioController::class, 'vistaConsulta'])->name('servicios.consulta.vista');

    Route::get('/servicios/listado', [AsignaServicioController::class, 'listadoVista'])
        ->name('servicios.listado.vista');

    Route::get('/servicios/listar', [AsignaServicioController::class, 'listarServicios'])
        ->name('servicios.listar');

    Route::post('/servicios/cancelar/{id}', [AsignaServicioController::class, 'cancelarServicio'])
        ->name('servicios.cancelar');

    Route::post('/servicios/audio', [AsignaServicioController::class, 'subirAudio'])
        ->name('servicios.audio');

    Route::get('/barrios/sugerencias', [BarrioController::class, 'sugerencias'])->name('barrios.sugerencias');

    // SANCIONES
    Route::get('/operadora/sancionar', [SancionesController::class, 'vistaSancionar'])
        ->name('operadora.sancionar');

    Route::get('/operadora/sanciones/levantar', [SancionesController::class, 'vistaLevantar'])
        ->name('operadora.sanciones.levantar_vista');

    Route::get('/operadora/sanciones/moviles-activos', [SancionesController::class, 'movilesActivos'])
        ->name('operadora.sanciones.moviles_activos');

    Route::post('/operadora/sanciones/registrar', [SancionesController::class, 'registrar'])
        ->name('operadora.sanciones.registrar');

    Route::get('/operadora/sanciones/listar', [SancionesController::class, 'listar'])
        ->name('operadora.sanciones.listar');

    Route::post('/operadora/sanciones/{id}/levantar', [SancionesController::class, 'levantar'])
        ->name('operadora.sanciones.levantar');

    // RECAUDO
    Route::get('/operadora/recaudado', [RecaudoController::class, 'vista'])
        ->name('operadora.recaudado');

    Route::get('/operadora/recaudado/pendientes', [RecaudoController::class, 'pendientes'])
        ->name('operadora.recaudado.pendientes');

    Route::post('/operadora/recaudado/pagar', [RecaudoController::class, 'pagar'])
        ->name('operadora.recaudado.pagar');

    Route::get('/operadora/recaudado/historial', [RecaudoHistorialController::class, 'vista'])
        ->name('operadora.recaudado.historial');

    Route::get('/operadora/recaudado/historial/listar', [RecaudoHistorialController::class, 'listar'])
        ->name('operadora.recaudado.historial.listar');

    Route::get('/operadora/recaudado/pendientes-cc', [RecaudoController::class, 'pendientesPorCedula'])
        ->name('operadora.recaudado.pendientes_cc');

    Route::get('/operadora/recaudado/buscar-conductores', [\App\Http\Controllers\Operadora\RecaudoController::class, 'buscarConductores'])
        ->name('operadora.recaudado.buscar_conductores');

    // FACTURACIÓN (operadora)
    Route::get('/facturacion', [\App\Http\Controllers\Operadora\FacturacionController::class, 'vista'])
        ->name('operadora.facturacion');

    Route::get('/facturacion/pendientes-cc', [\App\Http\Controllers\Operadora\FacturacionController::class, 'pendientesPorCedula'])
        ->name('operadora.facturacion.pendientes_cc');

    Route::get('/facturacion/buscar-conductores', [\App\Http\Controllers\Operadora\FacturacionController::class, 'buscarConductores'])
        ->name('operadora.facturacion.buscar_conductores');

    Route::post('/facturacion/facturar', [\App\Http\Controllers\Operadora\FacturacionController::class, 'facturar'])
        ->name('operadora.facturacion.facturar');

    Route::get('/facturas-pendientes', [\App\Http\Controllers\Operadora\RecaudoController::class, 'vistaPendientesGlobal'])
        ->name('operadora.facturas_pendientes');

    Route::get('/facturas-pendientes/listar', [\App\Http\Controllers\Operadora\RecaudoController::class, 'pendientesGlobal'])
        ->name('operadora.facturas_pendientes.listar');

    // ESTADO CONDUCTOR (operadora)
    Route::get('/operadora/estado-conductor', [EstadoConductorControllerOperadora::class, 'index'])
        ->name('operadora.estado_conductor.index');

    Route::get('/operadora/estado-conductor/buscar', [EstadoConductorControllerOperadora::class, 'buscar'])
        ->name('operadora.estado_conductor.buscar');

    Route::post('/operadora/estado-conductor/actualizar', [EstadoConductorControllerOperadora::class, 'actualizar'])
        ->name('operadora.estado_conductor.actualizar');

    // LICENCIAS (si esto es operadora/admin)
    Route::get('/conductores/licencia/buscar', [ConductorController::class, 'buscarConductoresParaLicencia'])
        ->name('conductores.licencia.buscar');

    Route::post('/conductores/licencia/detalle', [ConductorController::class, 'detalleConductorParaLicencia'])
        ->name('conductores.licencia.detalle');
});

/* =========================
| GRUPO: CONDUCTOR
========================= */
Route::middleware(['auth', RolMiddleware::class . ':conductor'])->group(function () {

    Route::get('/conductor/servicios', [ConductorServiciosController::class, 'vista'])
        ->name('conductor.servicios');

    Route::get('/conductor/servicios/listar', [ConductorServiciosController::class, 'listar'])
        ->name('conductor.servicios.listar');

    Route::get('/conductor/facturacion', [FacturacionControllerConductor::class, 'index'])
        ->name('conductor.facturacion.index');

    Route::get('/conductor/facturacion/listar', [FacturacionControllerConductor::class, 'listar'])
        ->name('conductor.facturacion.listar');

    Route::get('/conductor/servicios-asignados', [ServiciosAsignadosController::class, 'index'])
        ->name('conductor.servicios_asignados');

    Route::get('/conductor/servicios-asignados/listar', [ServiciosAsignadosController::class, 'listar'])
        ->name('conductor.servicios_asignados.listar');

    Route::get('/conductor/notificaciones', [NotificacionesController::class, 'index'])
        ->name('conductor.notificaciones');

    Route::get('/conductor/notificaciones/{id}/leer', [NotificacionesController::class, 'leer'])
        ->name('conductor.notificaciones.leer');

    Route::get('/conductor/notificaciones/leer-todas', [NotificacionesController::class, 'leerTodas'])
        ->name('conductor.notificaciones.leer_todas');

    Route::post('/conductor/push/subscribe', [PushController::class, 'subscribe'])
        ->name('conductor.push.subscribe');

    Route::get('/conductor/estado', [EstadoConductorController::class, 'index'])
        ->name('conductor.estado');

    Route::post('/conductor/estado', [EstadoConductorController::class, 'update'])
        ->name('conductor.estado.update');

    Route::get('/conductor/moviles', [MovilesController::class, 'index'])
        ->name('conductor.moviles');

    Route::post('/conductor/moviles/{movilId}/toggle', [MovilesController::class, 'toggle'])
        ->name('conductor.moviles.toggle');

    Route::get('/conductor/perfil', [PerfilController::class, 'edit'])
        ->name('conductor.perfil.edit');

    Route::post('/conductor/perfil', [PerfilController::class, 'update'])
        ->name('conductor.perfil.update');

    Route::get('/conductor/taxi', [TaxiConductorController::class, 'edit'])
        ->name('conductor.taxi.edit');

    Route::post('/conductor/taxi', [TaxiConductorController::class, 'update'])
        ->name('conductor.taxi.update');

    Route::get('/conductor/ultimo-servicio', [\App\Http\Controllers\Conductor\UltimoServicioController::class, 'show'])
        ->name('conductor.ultimo_servicio');
});
