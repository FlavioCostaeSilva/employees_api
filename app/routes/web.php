<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [HomeController::class, 'index']);

Route::get(
    '/{text}/{estado_id}',
    [HomeController::class, 'estado']
)
    ->where('text', '[A-Za-zÀ-ÿ\s+-]+')
    ->where('estado_id', '[0-9]+');

Route::get(
    '/{text}/{estado_id}/{cidade_id}',
    [HomeController::class, 'cidade']
)
    ->where('text', '[A-Za-zÀ-ÿ\s+-]+')
    ->where('estado_id', '[0-9]+')
    ->where('cidade_id', '[0-9]+');

Route::get(
    '/{text}/{estado_id}/{cidade_id}/{bairro_id}',
    [HomeController::class, 'cidade']
)
    ->where('text', '[A-Za-zÀ-ÿ\s+-]+')
    ->where('estado_id', '[0-9]+')
    ->where('cidade_id', '[0-9]+')
    ->where('bairro_id', '[0-9]+');
