<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\FermentationController;
use App\Http\Controllers\TurnController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReadingController;
use App\Http\Controllers\GenotypeController;

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

// Auth Routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
// Public Routes
Route::post('measurements', [MeasurementController::class, 'store']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Routes for Devices
    Route::get('devices', [DeviceController::class, 'index']);
    Route::post('devices', [DeviceController::class, 'store']);
    Route::get('devices/{id}', [DeviceController::class, 'show']);
    Route::put('devices/{id}/status', [DeviceController::class, 'updateStatus']);

    // Routes for Fermentations
    Route::get('fermentations', [FermentationController::class, 'index']);
    Route::post('fermentations', [FermentationController::class, 'store']);
    Route::get('fermentations/{id}', [FermentationController::class, 'show']);
    Route::put('fermentations/{id}/status', [FermentationController::class, 'updateStatus'])->name('fermentations.updateStatus');
    Route::get('fermentations/{id}/measurements', [FermentationController::class, 'getMeasurementsByFermentation']);
    Route::get('fermentations/{id}/measurements/{limit}', [FermentationController::class, 'getLastMeasurementsLimit']);

    // Routes for Turns
    Route::get('turns', [TurnController::class, 'index']);
    Route::post('turns', [TurnController::class, 'store']);
    Route::get('turns/{id}', [TurnController::class, 'show']);

    // Routes for Sensors
    Route::get('sensors', [SensorController::class, 'index']);
    Route::post('sensors', [SensorController::class, 'store']);
    Route::get('sensors/{id}', [SensorController::class, 'show']);

    // Protected Measurement Routes
    Route::get('measurements', [MeasurementController::class, 'index']);
    Route::get('measurements/{id}', [MeasurementController::class, 'show']);
    Route::delete('measurements/{id}', [MeasurementController::class, 'destroy']);

    // Rutas para Genotipos
    Route::get('genotypes', [GenotypeController::class, 'index']);
    Route::post('genotypes', [GenotypeController::class, 'store']);
    Route::get('genotypes/{id}', [GenotypeController::class, 'show']);
    Route::post('fermentations/{id}/genotypes', [GenotypeController::class, 'attachToFermentation']);
    Route::delete('fermentations/{id}/genotypes', [GenotypeController::class, 'detachFromFermentation']);
});
