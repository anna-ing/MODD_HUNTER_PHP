<?php

use App\Http\Controllers\SolicitudController;
use Illuminate\Support\Facades\Route;

// 1. Carga inicial: Renderiza la vista Blade con el listado inicial
Route::get('requests', [SolicitudController::class, 'listRequests'])->name('listRequests');

// 2. Acciones asíncronas (Devuelven respuestas JSON para Alpine.js)
Route::prefix('requests')->group(function () {
    // Crear una nueva solicitud
    Route::post('/', [SolicitudController::class, 'createRequest'])->name('createRequest');
    
    // Actualizar título y descripción (Edición)
    Route::post('/{id}', [SolicitudController::class, 'updateRequest'])->name('updateRequest');
    
    // Ejecutar transiciones de estado desde la interfaz (PATCH es ideal para actualizaciones parciales)
    Route::post('/{id}/transitions', [SolicitudController::class, 'change_status'])->name('change_status');
});
