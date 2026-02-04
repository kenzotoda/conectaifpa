<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EventController;
use App\Http\Controllers\Auth\RegisteredUserController;


Route::get('/', [EventController::class, 'index']);
Route::get('/events/load-more', [EventController::class, 'loadMore'])
     ->name('events.loadMore');
// ->middleware('auth') retorna por padrão para /login se não reconhecer usuário autenticado.
Route::get('/events/create', [EventController::class, 'create'])->middleware(['auth', 'isCoordinator']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::post('/events', [EventController::class, 'store'])->middleware(['auth', 'isCoordinator']);
Route::get('/dashboard', [EventController::class, 'dashboard'])->middleware('auth');
Route::delete('/events/{id}', [EventController::class, 'destroy'])->middleware(['auth', 'isCoordinator']);
Route::get('/events/edit/{id}', [EventController::class, 'edit'])->middleware(['auth', 'isCoordinator']);
Route::put('/events/update/{id}', [EventController::class, 'update'])->middleware(['auth', 'isCoordinator']);
Route::post('/events/join/{id}', [EventController::class, 'joinEvent'])->middleware('auth');
Route::delete('/events/leave/{id}', [EventController::class, 'leaveEvent'])->middleware('auth');


Route::middleware(['auth'])->group(function () {
    Route::get('/register/coordinator', [RegisteredUserController::class, 'create'])
        ->name('register.coordinator');

    Route::post('/register/coordinator', [RegisteredUserController::class, 'storeCoordinator']);
});

Route::get('/events/registered/{id}', [EventController::class, 'registered'])->middleware(['auth', 'isCoordinator']);

Route::get('/events/{id}/export-csv', [EventController::class, 'exportCsv'])->middleware(['auth', 'isCoordinator']);

Route::delete('/events/{eventId}/remove/{userId}', [EventController::class, 'removeParticipant'])->middleware(['auth', 'isCoordinator']);
// Route::get('/event/{id}', [EventController::class, 'newShow']);

Route::post('/events/validate-step', [EventController::class, 'validateStep'])
    ->name('events.validate-step');

