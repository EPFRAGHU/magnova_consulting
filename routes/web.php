<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginOtpController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractAttendanceController;
use App\Http\Controllers\ContractNilRegisterController;
use App\Http\Controllers\ContractWageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EcrController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\WageController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::post('login/otp/request', [LoginOtpController::class, 'request'])->name('login.otp.request');
    Route::post('login/otp/verify', [LoginOtpController::class, 'verify'])->name('login.otp.verify');
    Route::post('login/otp/resend', [LoginOtpController::class, 'resend'])->name('login.otp.resend');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', ClientController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::post('clients/{client}/regenerate-invoices', [ClientController::class, 'regenerateInvoices'])->name('clients.regenerate-invoices');
    Route::resource('employees', EmployeeController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/save', [AttendanceController::class, 'save'])->name('attendance.save');
    Route::post('attendance', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::post('attendance/contract', [ContractAttendanceController::class, 'save'])->name('attendance.contract.save');
    Route::get('attendance/contract/register', [ContractAttendanceController::class, 'register'])->name('attendance.contract.register');
    Route::get('attendance/sheet', [AttendanceController::class, 'sheet'])->name('attendance.sheet');

    Route::get('wages', [WageController::class, 'index'])->name('wages.index');
    Route::post('wages', [WageController::class, 'store'])->name('wages.store');
    Route::get('wages/register', [WageController::class, 'register'])->name('wages.register');
    Route::post('wages/contract', [ContractWageController::class, 'save'])->name('wages.contract.save');
    Route::get('wages/contract/register', [ContractWageController::class, 'register'])->name('wages.contract.register');
    Route::get('registers/contract/{kind}', [ContractNilRegisterController::class, 'show'])->whereIn('kind', ['fines', 'deductions'])->name('registers.contract.nil');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    Route::get('payslips', [PayslipController::class, 'index'])->name('payslips.index');
    Route::get('payslips/bulk', [PayslipController::class, 'bulk'])->name('payslips.bulk');
    Route::get('payslips/contract/{contractWage}', [PayslipController::class, 'contractShow'])->name('payslips.contract.show');
    Route::get('payslips/{wage}', [PayslipController::class, 'show'])->name('payslips.show');

    Route::get('ecr', [EcrController::class, 'index'])->name('ecr.index');
    Route::get('ecr/download', [EcrController::class, 'download'])->name('ecr.download');
});

require __DIR__.'/settings.php';
