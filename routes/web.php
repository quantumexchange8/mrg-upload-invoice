<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;

Route::get('locale/{locale}', function ($locale) {
    App::setLocale($locale);
    Session::put("locale", $locale);

    return redirect()->back();
});

Route::get('/', function () {
    return redirect(route('invoice'));
});

// Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('invoice')->group(function () {
    Route::get('/', [InvoiceController::class, 'index'])->name('invoice');
    Route::get('/getInvoicelisting', [InvoiceController::class, 'getInvoicelisting'])->name('invoice.getInvoicelisting');
    Route::get('/getInvoiceItems', [InvoiceController::class, 'getInvoiceItems'])->name('invoice.getInvoiceItems');

    Route::post('upload', [InvoiceController::class, 'upload'])->name('invoice.upload');
    Route::post( 'sendEmails', [InvoiceController::class, 'sendEmails'])->name('invoice.sendEmails');
});

require __DIR__ . '/auth.php';
