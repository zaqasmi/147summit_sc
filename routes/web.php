<?php

use App\Http\Controllers\CustomerDueReportExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->get(
    '/customer-dues/export/pdf',
    CustomerDueReportExportController::class,
)->name('customer-dues.export-pdf');
