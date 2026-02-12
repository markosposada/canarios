<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
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
use App\Http\Controllers\Operadora\SancionesController;
use App\Http\Controllers\Operadora\FacturacionController;
use App\Http\Controllers\Operadora\RecaudoController;
use App\Http\Controllers\Operadora\RecaudoHistorialController;
//ruta vista temporal formulario taxistas
use App\Http\Controllers\TaxistaController;
use App\Http\Controllers\Operadora\EstadoConductorControllerOperadora;
use App\Http\Controllers\Conductor\FacturacionControllerConductor;






/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Login
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/', function () {
    return redirect('/login');
});


Route::post('login', [LoginController::class, 'login']);

// Registro
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Recuperar contraseña
Route::get('forgot-password', function () {
    return 'Aquí va el formulario para recuperar la contraseña.';
})->name('password.request');

// Dashboard (solo autenticado)
/*Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth'); */

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');


// Módulo conductor (sin filtro por rol)
Route::get('/modulo-conductor', function () {
    return view('modulos.conductor');
})->middleware('auth');

// Módulo operadora (sin filtro por rol)
Route::get('/modulo-operadora', function () {
    return view('modulos.operadora');
})->middleware('auth');

// Cierre de sesión
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Redirección a login
Route::get('/index', function () {
    return view('auth.login');
});

Route::get('/servicios/consulta/buscar', [AsignaServicioController::class, 'buscarPorToken'])
    ->middleware('throttle:30,1') // máx 30 req/min por IP (ajusta a tu gusto)
    ->name('servicios.consulta.buscar');

Route::get('/taxis/crear', [TaxiController::class, 'create'])->name('taxis.create');
Route::post('/taxis', [TaxiController::class, 'store'])->name('taxis.store');

Route::get('/api/verificar-placa', function (\Illuminate\Http\Request $request) {
    $existe = DB::table('taxi')
        ->where('ta_placa', strtoupper($request->query('placa')))
        ->exists();
    return response()->json(['exists' => $existe]);
});


Route::get('/taxis/editar-fechas', [TaxiController::class, 'editDates'])->name('taxis.editDates');
Route::post('/taxis/actualizar-fechas', [TaxiController::class, 'updateDates'])->name('taxis.updateDates');
Route::get('/api/buscar-taxi', [TaxiController::class, 'buscarPorPlaca']);


Route::get('/taxis/panel', [TaxiController::class, 'panel'])->name('taxis.panel');
Route::post('/taxis/cambiar-estado', [TaxiController::class, 'cambiarEstado'])->name('taxis.cambiarEstado');


/* Mostrar formulario
Route::get('/conductores/crear', [ConductorController::class, 'create'])->name('conductores.create');

// Validar cédula via AJAX
Route::post('/conductores/buscar', [ConductorController::class, 'buscar'])->name('conductores.buscar');

// Guardar nuevo conductor
Route::post('/conductores/guardar', [ConductorController::class, 'store'])->name('conductores.store');
*/
///
Route::post('/conductores/buscar-cedula', [ConductorController::class, 'buscarPorCedulaParaEditar']);


Route::get('/conductores/editar-licencia', [ConductorController::class, 'editLicencia'])->name('conductores.editLicencia');
//Route::post('/api/buscar-conductor', [ConductorController::class, 'buscarPorCedulaParaEditar']);
Route::post('/buscar-conductor', [ConductorController::class, 'buscarPorCedulaParaEditar']);

Route::post('/conductores/actualizar-licencia', [ConductorController::class, 'actualizarLicencia'])->name('conductores.actualizarLicencia');

Route::get('/conductores/asignar', [ConductorController::class, 'asignar'])->name('asignar.form');
Route::post('/buscar-datos-conductor', [ConductorController::class, 'buscarDatosConductor']);
Route::post('/asignar-conductor', [ConductorController::class, 'guardarAsignacion'])->name('asignar.guardar');


Route::get('/conductores', [ConductorController::class, 'panelConductores'])->name('conductores.panel');
//Route::get('/conductores/buscar', [ConductorController::class, 'buscarMovil'])->name('conductores.buscar');
//Route::post('/conductores/{id}/estado', [ConductorController::class, 'actualizarEstado'])->name('conductores.actualizarEstado');
Route::get('/conductores/buscar-ajax', [ConductorController::class, 'buscarMovilAjax'])->name('conductores.buscarAjax');
//Route::post('/conductores/actualizar-estado/{movil}', [ConductorController::class, 'actualizarEstado'])->name('conductores.actualizarEstado');
//Route::post('/conductores/actualizar-estado/{id}', [ConductorController::class, 'actualizarEstado'])->name('conductores.actualizarEstado');


//Route::post('/conductores/movil/{id}/estado', [ConductorController::class, 'actualizarEstado'])
  //  ->name('conductores.estado');

Route::post('/conductores/movil/{id}/estado', [ConductorController::class, 'actualizarEstado'])
    ->name('conductores.actualizarEstado');


Route::get('/servicios/asignar', [AsignaServicioController::class, 'vista'])->name('servicios.vista');
Route::get('/servicios/moviles', [AsignaServicioController::class, 'movilesActivos'])->name('servicios.moviles'); // AJAX
Route::post('/servicios/registrar', [AsignaServicioController::class, 'registrar'])->name('servicios.registrar'); // disponible


Route::get('/servicios/consulta', [AsignaServicioController::class, 'vistaConsulta'])->name('servicios.consulta.vista');


// Vista del listado
Route::get('/servicios/listado', [AsignaServicioController::class, 'listadoVista'])
    ->name('servicios.listado.vista');

// Datos para la tabla (AJAX)
Route::get('/servicios/listar', [AsignaServicioController::class, 'listarServicios'])
    ->name('servicios.listar');

// Cancelar servicio (AJAX)
Route::post('/servicios/cancelar/{id}', [AsignaServicioController::class, 'cancelarServicio'])
    ->name('servicios.cancelar');

Route::get('/barrios/sugerencias', [BarrioController::class, 'sugerencias'])->name('barrios.sugerencias');

    Route::post('/servicios/audio', [AsignaServicioController::class, 'subirAudio'])
    ->name('servicios.audio');


//ruta temporal formulario taxitas
Route::get('/taxistas/crear', [TaxistaController::class, 'create'])->name('taxistas.create');
Route::post('/taxistas', [TaxistaController::class, 'store'])->name('taxistas.store');
Route::get('/taxistas/guardado', function () {
    return view('taxistas.success');
})->name('taxistas.success');



Route::middleware(['auth'])->group(function () {
    //inicio operadora
// Página 1: Sancionar
    Route::get('/operadora/sancionar', [SancionesController::class, 'vistaSancionar'])
        ->name('operadora.sancionar');

    // Página 2: Anular/Levantar
    Route::get('/operadora/sanciones/levantar', [SancionesController::class, 'vistaLevantar'])
        ->name('operadora.sanciones.levantar_vista');

    // AJAX
    Route::get('/operadora/sanciones/moviles-activos', [SancionesController::class, 'movilesActivos'])
        ->name('operadora.sanciones.moviles_activos');

    Route::post('/operadora/sanciones/registrar', [SancionesController::class, 'registrar'])
        ->name('operadora.sanciones.registrar');

    Route::get('/operadora/sanciones/listar', [SancionesController::class, 'listar'])
        ->name('operadora.sanciones.listar');

    Route::post('/operadora/sanciones/{id}/levantar', [SancionesController::class, 'levantar'])
        ->name('operadora.sanciones.levantar');

        Route::get('/operadora/facturacion', [FacturacionController::class, 'vista'])
        ->name('operadora.facturacion');

    Route::get('/operadora/facturacion/pendientes', [FacturacionController::class, 'pendientes'])
        ->name('operadora.facturacion.pendientes');

    Route::post('/operadora/facturacion/facturar', [FacturacionController::class, 'facturar'])
        ->name('operadora.facturacion.facturar');

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

Route::get('/operadora/recaudado/pendientes-cc', [\App\Http\Controllers\Operadora\RecaudoController::class, 'pendientesPorCedula'])
    ->name('operadora.recaudado.pendientes_cc');

    Route::get('/facturacion', [\App\Http\Controllers\Operadora\FacturacionController::class, 'vista'])->name('operadora.facturacion');

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

        Route::get('/conductores/asignar/buscar', [App\Http\Controllers\ConductorController::class, 'buscarConductoresAsignar'])
    ->name('conductores.asignar.buscar');

Route::post('/buscar-datos-conductor', [App\Http\Controllers\ConductorController::class, 'buscarDatosConductor'])

    ->name('conductores.datos');

    Route::get('/operadora/estado-conductor', [EstadoConductorControllerOperadora::class, 'index'])->name('operadora.estado_conductor.index');
    Route::get('/operadora/estado-conductor/buscar', [EstadoConductorControllerOperadora::class, 'buscar'])->name('operadora.estado_conductor.buscar');
    Route::post('/operadora/estado-conductor/actualizar', [EstadoConductorControllerOperadora::class, 'actualizar'])->name('operadora.estado_conductor.actualizar');

//fin operadora

    Route::get('/conductor/servicios', [ConductorServiciosController::class, 'vista'])
        ->name('conductor.servicios');

    Route::get('/conductor/servicios/listar', [ConductorServiciosController::class, 'listar'])
        ->name('conductor.servicios.listar');


    
    //codigo nuevo

    Route::get('/conductor/facturacion',[FacturacionControllerConductor::class, 'index'])->name('conductor.facturacion.index');

Route::get('/conductor/facturacion/listar',[FacturacionControllerConductor::class, 'listar'])->name('conductor.facturacion.listar');

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

    Route::post('/conductor/push/subscribe', [PushController::class, 'subscribe'])->name('conductor.push.subscribe');

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

    // Taxi del conductor (solo el asignado)
Route::get('/conductor/taxi', [TaxiConductorController::class, 'edit'])
    ->name('conductor.taxi.edit');

Route::post('/conductor/taxi', [TaxiConductorController::class, 'update'])
    ->name('conductor.taxi.update');

Route::get(
    '/conductor/ultimo-servicio',
    [\App\Http\Controllers\Conductor\UltimoServicioController::class, 'show']
)->name('conductor.ultimo_servicio');


});