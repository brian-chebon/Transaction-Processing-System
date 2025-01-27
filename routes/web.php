<?php

use Illuminate\Support\Facades\Route;

// Include API routes
require base_path('routes/api.php');

// Default home route
Route::get('/', function () {
    return view('welcome');
});
