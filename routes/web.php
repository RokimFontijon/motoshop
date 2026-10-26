<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobOrderController;
use App\Http\Controllers\MotorcycleController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| The request flow (MVC): route -> middleware -> controller -> model -> view.
|
|   guest      only people who are NOT logged in (login / register)
|   auth       only logged-in people
|   db.session tells PostgreSQL who is logged in (row level security)
|   role:admin only admins
*/

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware(['auth', 'db.session'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Admins and mechanics: look, add and edit.
    Route::resource('customers', CustomerController::class)->except('destroy');
    Route::resource('motorcycles', MotorcycleController::class)->except('destroy');
    Route::resource('job-orders', JobOrderController::class)->except('destroy')
        ->parameters(['job-orders' => 'jobOrder']);
    Route::post('job-orders/{jobOrder}/parts', [JobOrderController::class, 'addPart'])->name('job-orders.parts.store');
    Route::delete('job-orders/{jobOrder}/parts/{line}', [JobOrderController::class, 'removePart'])->name('job-orders.parts.destroy');
    Route::get('parts', [PartController::class, 'index'])->name('parts.index');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    // Admins only: deleting records, managing the inventory and the staff accounts.
    Route::middleware('role:admin')->group(function () {
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::delete('motorcycles/{motorcycle}', [MotorcycleController::class, 'destroy'])->name('motorcycles.destroy');
        Route::delete('job-orders/{jobOrder}', [JobOrderController::class, 'destroy'])->name('job-orders.destroy');
        Route::resource('parts', PartController::class)->except(['index', 'show']);
        Route::resource('users', UserController::class)->except('show');
    });
});
