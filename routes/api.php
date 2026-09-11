<?php

use App\Http\Controllers\Api\V1\ContactController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/v1/contacts', [ContactController::class, 'index']);
Route::get('/v1/contacts/{contact}', [ContactController::class, 'show']);
Route::post('/v1/contacts', [ContactController::class, 'store']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
