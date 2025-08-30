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

    // Admin Settings Routes
    Route::get('/admin/settings', function () {
        return Inertia::render('admin/settings/index');
    })->name('admin.settings.index');

    Route::get('/admin/settings/business-types', function () {
        return Inertia::render('admin/settings/business-types');
    })->name('admin.settings.business-types');

    Route::get('/admin/settings/current-systems', function () {
        return Inertia::render('admin/settings/current-systems');
    })->name('admin.settings.current-systems');

    Route::get('/admin/settings/groups', function () {
        return Inertia::render('admin/settings/groups');
    })->name('admin.settings.groups');

    Route::get('/admin/settings/lead-status', function () {
        return Inertia::render('admin/settings/lead-status');
    })->name('admin.settings.lead-status');

    Route::get('/admin/settings/plans', function () {
        return Inertia::render('admin/settings/plans');
    })->name('admin.settings.plans');

    Route::get('/admin/settings/preferences', function () {
        return Inertia::render('admin/settings/preferences');
    })->name('admin.settings.preferences');

    Route::get('/admin/settings/roles', function () {
        return Inertia::render('admin/settings/roles');
    })->name('admin.settings.roles');

    Route::get('/admin/settings/targets', function () {
        return Inertia::render('admin/settings/targets');
    })->name('admin.settings.targets');
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
