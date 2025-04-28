<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('invoice_submit', function () {
    return Inertia::render('InvoiceSubmit');
})->middleware(['auth', 'verified'])->name('invoice_submit');


Route::post('/invoices/upload', [InvoiceController::class, 'upload']);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
