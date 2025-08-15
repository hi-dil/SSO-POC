<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Telescope routes (only in development)
if (app()->environment('local', 'testing')) {
    Route::get('/telescope', function () {
        return redirect('/telescope/requests');
    });
}
