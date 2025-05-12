<?php

use Illuminate\Support\Facades\Route;

// Route d'accueil
Route::get('/', function () {
    return view('welcome');
});

// Route de login pour la redirection des middleware
Route::get('/login', function () {
    return response()->json(['message' => 'Veuillez vous connecter'], 401);
})->name('login');