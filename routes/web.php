<?php

use App\Http\Controllers\JLibController;
use App\Http\Controllers\WebController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

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

Route::any('/JLib', [JLibController::class, 'index']);
Route::group([
    'prefix' => 'web'
], function (Router $router) {
    $router->any('query', [WebController::class, 'query']);
    $router->any('rand', [WebController::class, 'rand']);
});

