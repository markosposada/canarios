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







/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Login
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

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
Route::get('/servicios/consulta/buscar', [AsignaServicioController::class, 'buscarPorToken'])
    ->middleware('throttle:30,1') // máx 30 req/min por IP (ajusta a tu gusto)
    ->name('servicios.consulta.buscar');

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

Route::middleware(['auth'])->group(function () {
    Route::get('/conductor/servicios', [ConductorServiciosController::class, 'vista'])
        ->name('conductor.servicios');

    Route::get('/conductor/servicios/listar', [ConductorServiciosController::class, 'listar'])
        ->name('conductor.servicios.listar');
});