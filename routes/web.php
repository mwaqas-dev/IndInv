<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/products', [ProductController::class, 'index'])->middleware('permission:inventory.view')->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->middleware('permission:inventory.create')->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->middleware('permission:inventory.create')->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->middleware('permission:inventory.view')->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->middleware('permission:inventory.update')->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('permission:inventory.update')->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('permission:inventory.delete')->name('products.destroy');
    Route::get('/products/{product}/history', [InventoryController::class, 'history'])->middleware('permission:inventory.history')->name('products.history');
    Route::get('/inventory', [InventoryController::class, 'index'])->middleware('permission:inventory.view')->name('inventory.index');
    Route::get('/inventory/transaction', [InventoryController::class, 'create'])->middleware('permission:inventory.stock-in')->name('inventory.create');
    Route::post('/inventory/transaction', [InventoryController::class, 'store'])->middleware('permission:inventory.stock-in')->name('inventory.store');
    Route::get('/releases', [ReleaseController::class, 'index'])->middleware('permission:releases.view')->name('releases.index');
    Route::get('/releases/create', [ReleaseController::class, 'create'])->middleware('permission:releases.create')->name('releases.create');
    Route::post('/releases', [ReleaseController::class, 'store'])->middleware('permission:releases.create')->name('releases.store');
    Route::get('/releases/{release}', [ReleaseController::class, 'show'])->middleware('permission:releases.view')->name('releases.show');
    Route::get('/releases/{release}/edit', [ReleaseController::class, 'edit'])->middleware('permission:releases.update')->name('releases.edit');
    Route::put('/releases/{release}', [ReleaseController::class, 'update'])->middleware('permission:releases.update')->name('releases.update');
    Route::post('/releases/{release}/transition', [ReleaseController::class, 'transition'])->middleware('permission:releases.view')->name('releases.transition');
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
    Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->middleware('permission:users.deactivate')->name('users.toggle');
    Route::get('/activity', [ActivityController::class, 'index'])->middleware('permission:activity.view')->name('activity.index');
    Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
    Route::get('/reports/inventory.csv', [ReportController::class, 'inventoryCsv'])->middleware('permission:reports.view')->name('reports.inventory.csv');
    Route::post('/attachments/{type}/{id}', [AttachmentController::class, 'store'])->middleware('permission:inventory.update')->whereIn('type',['product', 'release'])->name('attachments.store');
});
