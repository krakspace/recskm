<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'login')->name('login');
Route::view('/dashboard', 'dashboard')->name('dashboard');
Route::view('/form_SOP', 'form_SOP')->name('form_SOP');