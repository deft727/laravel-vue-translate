<?php

use Illuminate\Http\Request;
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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('/upload-file', 'APIController@uploadFile')->where('any', '.*');
Route::post('/convert-file', 'APIController@convertFile')->where('any', '.*');
Route::post('/translate-html', 'APIController@translateHTML')->where('any', '.*');
