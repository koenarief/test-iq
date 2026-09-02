<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Disc\DiscTestController;
use App\Http\Controllers\Admin\IstAnswerKeyController;
use App\Http\Controllers\Admin\IstQuestionController;
use App\Http\Controllers\Admin\IstResultController;
use App\Http\Controllers\Admin\DiscResultController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Merchant\DashboardController as MerchantDashboardController;
use App\Http\Controllers\Merchant\IstResultController as MerchantIstResultController;
use App\Http\Controllers\Merchant\DiscResultController as MerchantDiscResultController;
use Inertia\Inertia;

Route::get('/', [LandingController::class, 'index'])
    ->name('landing');

/*
|--------------------------------------------------------------------------
| DISC
|--------------------------------------------------------------------------
*/

Route::prefix('disc')
    ->name('disc.')
    ->group(function () {

        Route::get('/', [DiscTestController::class, 'index'])
            ->name('index');

        Route::get('/m/{merchant}', [DiscTestController::class, 'index'])
            ->whereUuid('merchant')
            ->name('index.merchant');

        Route::post('/start', [DiscTestController::class, 'start'])
            ->name('start');

        Route::get('/instruction/{discTest}', [DiscTestController::class, 'instruction'])
            ->name('instruction');

        Route::get('/test/{discTest}', [DiscTestController::class, 'test'])
            ->name('test');

        Route::post('/test/{discTest}/submit', [DiscTestController::class, 'submit'])
            ->name('submit');

        Route::get('/result/{discTest}', [DiscTestController::class, 'result'])
            ->name('result');
    });

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::middleware('admin')->group(function () {

        Route::get('/dashboard', function () {
            return inertia('Admin/Dashboard');
        })->name('dashboard');

        Route::prefix('admin')
            ->name('admin.')
            ->group(function () {
                Route::resource('ist-questions', IstQuestionController::class)
                    ->parameters(['ist-questions' => 'question'])
                    ->except('show');

                Route::resource('ist-answer-keys', IstAnswerKeyController::class)
                    ->parameters(['ist-answer-keys' => 'answerKey'])
                    ->except('show');

                Route::get('ist-results', [IstResultController::class, 'index'])
                    ->name('ist-results.index');
                Route::get('ist-results/{test:public_id}', [IstResultController::class, 'show'])
                    ->whereUuid('test')
                    ->name('ist-results.show');

                Route::get('disc-results', [DiscResultController::class, 'index'])
                    ->name('disc-results.index');
                Route::get('disc-results/{discTest}', [DiscResultController::class, 'show'])
                    ->name('disc-results.show');

                Route::resource('users', UserController::class)
                    ->except('show');

                Route::resource('merchants', MerchantController::class)
                    ->except('show')
                    ->where(['merchant' => '[\da-fA-F]{8}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{12}']);
            });
    });

    Route::middleware('merchant')
        ->prefix('merchant')
        ->name('merchant.')
        ->group(function () {
            Route::get('/dashboard', [MerchantDashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('ist-results', [MerchantIstResultController::class, 'index'])
                ->name('ist-results.index');
            Route::get('ist-results/{test:public_id}', [MerchantIstResultController::class, 'show'])
                ->whereUuid('test')
                ->name('ist-results.show');

            Route::get('disc-results', [MerchantDiscResultController::class, 'index'])
                ->name('disc-results.index');
            Route::get('disc-results/{discTest}', [MerchantDiscResultController::class, 'show'])
                ->name('disc-results.show');
        });
});

Route::get('/ist/fa-preview', function () {
    return Inertia::render('IST/FaPreview');
});

require __DIR__.'/auth.php';
require __DIR__.'/ist.php';
