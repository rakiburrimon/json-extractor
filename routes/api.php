<?php

use App\Http\Controllers\JsonExtractionController;
use Illuminate\Support\Facades\Route;

Route::post('/extract-json', [JsonExtractionController::class, 'extract']);
