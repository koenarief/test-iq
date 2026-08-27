<?php

use App\Http\Controllers\Ist\IstAnswerController;
use App\Http\Controllers\Ist\IstMeExampleController;
use App\Http\Controllers\Ist\IstResultController;
use App\Http\Controllers\Ist\IstSubtestInstructionController;
use App\Http\Controllers\Ist\IstSubtestSessionController;
use App\Http\Controllers\Ist\IstTestController;
use App\Http\Middleware\Ist\EnsureIstRehearsalPreviewAccess;
use App\Http\Middleware\Ist\EnsureIstTestOwnership;
use App\Http\Middleware\Ist\ResolveIstRuntimeSubtest;
use Illuminate\Support\Facades\Route;

Route::prefix('ist')->name('ist.')
    ->middleware(EnsureIstRehearsalPreviewAccess::class)
    ->group(function (): void {
        Route::get('/', [IstTestController::class, 'index'])->name('index');
        Route::get('/m/{merchant}', [IstTestController::class, 'index'])
            ->whereUuid('merchant')
            ->name('index.merchant');
        Route::post('/start', [IstTestController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('start');

        Route::prefix('{test:public_id}')
            ->whereUuid('test')
            ->middleware(EnsureIstTestOwnership::class)
            ->group(function (): void {
                Route::get('/resume', [IstTestController::class, 'resume'])->name('resume');
                Route::get('/result', [IstResultController::class, 'show'])->name('result');

                Route::prefix('subtests/{subtest}')
                    ->whereIn('subtest', ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'])
                    ->middleware(ResolveIstRuntimeSubtest::class)
                    ->group(function (): void {
                        Route::get('/instruction', [IstSubtestInstructionController::class, 'show'])
                            ->name('subtests.instruction');
                        Route::post('/example/complete', [IstMeExampleController::class, 'complete'])
                            ->middleware('throttle:30,1')
                            ->name('subtests.example.complete');
                        Route::post('/start', [IstSubtestSessionController::class, 'start'])
                            ->middleware('throttle:30,1')
                            ->name('subtests.start');
                        Route::get('/work', [IstSubtestSessionController::class, 'work'])
                            ->name('subtests.work');
                        Route::put('/answers', [IstAnswerController::class, 'update'])
                            ->middleware('throttle:120,1')
                            ->name('subtests.answers.update');
                        Route::post('/finish', [IstSubtestSessionController::class, 'finish'])
                            ->middleware('throttle:30,1')
                            ->name('subtests.finish');
                    });
            });
    });
