<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// employee routes
Route::get('/employee', [EmployeeController::class, 'index'])
    ->name('employees.index');//ดึงข้อมูลพนักงานทั้งหมด (ใช้แสดงรายการพนักงาน)

Route::get('/employee/create', [EmployeeController::class, 'create'])
    ->name('employees.create');//แสดงฟอร์มสำหรับเพิ่มพนักงาน

Route::post('/employee', [EmployeeController::class, 'store'])
    ->name('employees.store');//รับข้อมูลจากฟอร์มแล้วบันทึกลงฐานข้อมูล

require __DIR__ . '/auth.php';
