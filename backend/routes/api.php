<?php

use App\Http\Controllers\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API routes
Route::get('/health', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API is running',
        'timestamp' => now()
    ]);
});

// Client Management API Routes
Route::prefix('v1')->group(function () {
    Route::post('clients/import', [ClientController::class, 'importCsv']);
    Route::get('clients/export', [ClientController::class, 'exportCsv']);
    Route::get('clients/duplicates/groups', [ClientController::class, 'getDuplicateGroups']);
    Route::get('clients/stats', [ClientController::class, 'getStats']);
    Route::delete('clients/delete-all', [ClientController::class, 'deleteAll']);
    Route::apiResource('clients', ClientController::class);
});

// Example API routes
Route::prefix('v1')->group(function () {
    Route::get('/test', function () {
        return response()->json([
            'message' => 'API is working!',
            'version' => '1.0.0'
        ]);
    });
});
