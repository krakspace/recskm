<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'login')->name('login');
Route::view('/dashboard', 'dashboard')->name('dashboard');
Route::view('/form_SOP', 'form_SOP')->name('form_SOP');
foreach (config('sidebar') as $section => $items) {
    foreach ($items as $label => $name) {
        if ($name === 'form_SOP') {
            continue;
        }

        Route::view('/'.str_replace('.', '/', $name), 'sidebar-page', [
            'section' => $section,
            'label' => $label,
        ])->name($name);
    }
}
