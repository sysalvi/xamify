<?php

use App\Http\Controllers\Api\StudentExamController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [StudentExamController::class, 'login'])->middleware('exam.browser');
Route::post('/heartbeat', [StudentExamController::class, 'heartbeat']);
Route::post('/violation', [StudentExamController::class, 'violation']);
Route::post('/handshake-ext', [StudentExamController::class, 'handshakeExtension']);
Route::post('/finish', [StudentExamController::class, 'finish']);
