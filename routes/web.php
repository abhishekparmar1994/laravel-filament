<?php

use App\Support\ImpersonationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): RedirectResponse => redirect('/admin'));

Route::post('/admin/impersonation/stop', function (): RedirectResponse {
    ImpersonationManager::stop();

    return redirect('/admin');
})->middleware(['web', 'auth'])->name('impersonation.stop');
