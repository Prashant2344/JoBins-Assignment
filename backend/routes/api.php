<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\MonitoringController;
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

Route::prefix('v1')->middleware('api.rate_limit:60,1')->group(function () {
    Route::middleware('api.rate_limit:10,1')->group(function () {
        Route::post('clients/import', [ClientController::class, 'importCsv']);
        Route::get('clients/export', [ClientController::class, 'exportCsv']);
        Route::delete('clients/delete-all', [ClientController::class, 'deleteAll']);
    });
    
    Route::get('clients/duplicates/groups', [ClientController::class, 'getDuplicateGroups']);
    Route::get('clients/duplicates/groups/{groupId}/clients', [ClientController::class, 'getDuplicateGroupClients']);
    Route::get('clients/stats', [ClientController::class, 'getStats']);
    Route::get('clients/batch-config', [ClientController::class, 'getBatchConfig']);
    Route::apiResource('clients', ClientController::class);
});

// Monitoring API Routes with stricter rate limiting
Route::prefix('v1/monitoring')->middleware('api.rate_limit:30,1')->group(function () {
    Route::get('stats', [MonitoringController::class, 'getStats']);
    Route::get('endpoints', [MonitoringController::class, 'getEndpointStats']);
    Route::get('ips', [MonitoringController::class, 'getIpStats']);
    Route::get('hourly', [MonitoringController::class, 'getHourlyStats']);
    Route::get('health', [MonitoringController::class, 'getHealth']);
    Route::get('dashboard', [MonitoringController::class, 'getDashboard']);
    Route::post('clean-logs', [MonitoringController::class, 'cleanLogs']);
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
