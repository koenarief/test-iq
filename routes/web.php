<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AssessmentSequenceController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Disc\DiscTestController;
use App\Http\Controllers\Competency\CompetencyTestController;
use App\Http\Controllers\Admin\IstAnswerKeyController;
use App\Http\Controllers\Admin\IstQuestionController;
use App\Http\Controllers\Admin\IstResultController;
use App\Http\Controllers\Admin\DiscResultController;
use App\Http\Controllers\Admin\CompetencyResultController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Merchant\DashboardController as MerchantDashboardController;
use App\Http\Controllers\Merchant\IstResultController as MerchantIstResultController;
use App\Http\Controllers\Merchant\DiscResultController as MerchantDiscResultController;
use App\Http\Controllers\Merchant\CompetencyResultController as MerchantCompetencyResultController;
use App\Http\Controllers\Candidate\CandidateDashboardController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\InterviewScoreController;
use App\Http\Controllers\Admin\AssessmentRecapController;
use App\Http\Controllers\Admin\DashboardController;
use Inertia\Inertia;

Route::get('/', [LandingController::class, 'index'])
    ->name('landing');

/*
|--------------------------------------------------------------------------
| RANGKAIAN TES (DISC -> IST -> KOMPETENSI)
|--------------------------------------------------------------------------
|
| Satu biodata di depan, tanpa login, lalu otomatis berjalan berurutan
| lewat ketiga modul. Lihat juga routes/ist.php untuk rute "ist.resume"
| yang dipanggil oleh continueToIst().
|
*/

Route::prefix('tes')
    ->name('assessment.')
    ->group(function () {
        Route::get('/', [AssessmentSequenceController::class, 'start'])
            ->name('start');

        Route::get('/m/{merchant:slug}', [AssessmentSequenceController::class, 'start'])
            ->where('merchant', '[a-z0-9-]+')
            ->name('start.merchant');

        Route::post('/mulai', [AssessmentSequenceController::class, 'begin'])
            ->name('begin');

        Route::middleware('auth')->group(function () {
            Route::post('/lanjut/ist', [AssessmentSequenceController::class, 'continueToIst'])
                ->name('continue.ist');

            Route::post('/lanjut/kompetensi', [AssessmentSequenceController::class, 'continueToCompetency'])
                ->name('continue.competency');
        });
    });

/*
|--------------------------------------------------------------------------
| DISC
|--------------------------------------------------------------------------
*/

Route::prefix('disc')
    ->name('disc.')
    ->middleware('auth')
    ->group(function () {

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
| TES KOMPETENSI
|--------------------------------------------------------------------------
*/

Route::prefix('competency')
    ->name('competency.')
    ->middleware('auth')
    ->group(function () {

        Route::get('/instruction/{competencyTest}', [CompetencyTestController::class, 'instruction'])
            ->name('instruction');

        Route::get('/test/{competencyTest}', [CompetencyTestController::class, 'test'])
            ->name('test');

        Route::post('/test/{competencyTest}/submit', [CompetencyTestController::class, 'submit'])
            ->name('submit');

        Route::get('/result/{competencyTest}', [CompetencyTestController::class, 'result'])
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

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::prefix('admin')
            ->name('admin.')
            ->group(function () {
                Route::get('ist-questions/review', [IstQuestionController::class, 'review'])
                    ->name('ist-questions.review');

                Route::get('ist-questions/me-preview', [IstQuestionController::class, 'mePreview'])
                    ->name('ist-questions.me-preview');

                Route::post('ist-questions/{question}/image', [IstQuestionController::class, 'uploadImage'])
                    ->name('ist-questions.upload-image');

                Route::post('ist-questions/{question}/options/{option}/image', [IstQuestionController::class, 'uploadOptionImage'])
                    ->name('ist-questions.upload-option-image');

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

                Route::get('competency-results', [CompetencyResultController::class, 'index'])
                    ->name('competency-results.index');
                Route::get('competency-results/{competencyTest}', [CompetencyResultController::class, 'show'])
                    ->name('competency-results.show');

                Route::resource('users', UserController::class)
                    ->except('show');

                Route::resource('merchants', MerchantController::class)
                    ->except('show')
                    ->where(['merchant' => '[\da-fA-F]{8}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{12}']);

                Route::resource('positions', PositionController::class)
                    ->except('show');

                Route::get('assessment-recaps', [AssessmentRecapController::class, 'index'])
                    ->name('assessment-recaps.index');
                Route::get('assessment-recaps/{candidateAssessment}', [AssessmentRecapController::class, 'show'])
                    ->name('assessment-recaps.show');

                Route::get('candidate-assessments/{candidateAssessment}/interview', [InterviewScoreController::class, 'edit'])
                    ->name('candidate-assessments.interview.edit');
                Route::put('candidate-assessments/{candidateAssessment}/interview', [InterviewScoreController::class, 'update'])
                    ->name('candidate-assessments.interview.update');
            });
    });

    Route::middleware('candidate')
        ->prefix('candidate')
        ->name('candidate.')
        ->group(function () {
            Route::get('/dashboard', [CandidateDashboardController::class, 'index'])
                ->name('dashboard');
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

            Route::get('competency-results', [MerchantCompetencyResultController::class, 'index'])
                ->name('competency-results.index');
            Route::get('competency-results/{competencyTest}', [MerchantCompetencyResultController::class, 'show'])
                ->name('competency-results.show');
        });
});

Route::get('/ist/fa-preview', function () {
    return Inertia::render('IST/FaPreview');
});

require __DIR__.'/auth.php';
require __DIR__.'/ist.php';
require __DIR__.'/absensi.php';
