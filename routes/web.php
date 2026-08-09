<?php

use App\Http\Controllers\CustomerDueReportExportController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebsiteController::class, 'home'])->name('website.home');
Route::get('/about', [WebsiteController::class, 'about'])->name('website.about');
Route::get('/live/current-tournament', [WebsiteController::class, 'currentTournamentLive'])->name('website.live.current-tournament');
Route::get('/tournaments', [WebsiteController::class, 'tournaments'])->name('website.tournaments');
Route::get('/tournaments/{tournament}', [WebsiteController::class, 'tournament'])->name('website.tournament');
Route::get('/tournaments/{tournament}/live', [WebsiteController::class, 'tournamentLive'])->name('website.tournament.live');
Route::get('/news/{newsPost}', [WebsiteController::class, 'news'])->name('website.news');
Route::get('/pages/{cmsPage}', [WebsiteController::class, 'page'])->name('website.page');
Route::post('/contact', [WebsiteController::class, 'contact'])->name('website.contact');

Route::middleware('auth')->get(
    '/customer-dues/export/pdf',
    CustomerDueReportExportController::class,
)->name('customer-dues.export-pdf');
