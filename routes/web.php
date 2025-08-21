<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['role:Admin,Manager','auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    
    Route::get('users', function () {
        return Inertia::render('users');
    })->name('users');
    
    Route::get('leads', function () {
        return Inertia::render('leads');
    })->name('leads');

    Route::get('leads/{lead}', function (\App\Models\Lead $lead) {
        return Inertia::render('lead', [
            'leadId' => $lead->id,
        ]);
    })->name('lead.show');    
});

Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/admin-panel', function () {
        return view('admin.panel');
    });
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
