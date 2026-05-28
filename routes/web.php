<?php

use App\Http\Controllers\IncidentMapController;
use Illuminate\Support\Facades\Route;

// Main HTML Frontend Dashboard View
Route::get('/', [IncidentMapController::class, 'index']);

// Javascript API Data Endpoint
Route::get('/api/incidents', [IncidentMapController::class, 'getIncidents']);