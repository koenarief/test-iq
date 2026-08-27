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

    Route::get('/dashboard', function () {
        return inertia('Admin/Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

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
        });
});

Route::get('/ist/fa-preview', function () {
    return Inertia::render('IST/FaPreview');
});

require __DIR__.'/auth.php';
require __DIR__.'/ist.php';
