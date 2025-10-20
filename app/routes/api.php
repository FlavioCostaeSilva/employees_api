<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeUploadCSVController;
use App\Http\Controllers\ManagerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [ManagerController::class, 'logout']);
    Route::get('/me', [ManagerController::class, 'me']);

    Route::apiResource('/employees', EmployeeController::class);
    Route::post('/employees/upload-csv', [EmployeeUploadCSVController::class, 'uploadCsv']);
});

Route::post('/login', [ManagerController::class, 'login']);
Route::post('/register', [ManagerController::class, 'register']);
