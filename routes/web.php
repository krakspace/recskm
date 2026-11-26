<?php

use App\Http\Controllers\DraftBackupController;
use App\Http\Controllers\SimulationController;
use App\Http\Middleware\UseSimulationDatabase;
use Illuminate\Support\Facades\Route;

Route::view('/', 'login')->name('login');
Route::view('/dashboard', 'dashboard')->name('dashboard');

Route::view('/pengelolaan/draf-sop', 'pengelolaan.draf-sop')->name('pengelolaan.draf-sop');
Route::view('/pengelolaan/dalam-pengajuan', 'pengelolaan.dalam-pengajuan')->name('pengelolaan.dalam-pengajuan');
Route::view('/pengelolaan/perlu-revisi', 'pengelolaan.perlu-revisi')->name('pengelolaan.perlu-revisi');

Route::view('/pemeriksaan/antrean-supervisor', 'pengelolaan.dalam-pengajuan', ['supervisor' => true])
    ->name('pemeriksaan.antrean-supervisor');

Route::get('/verify/{uuid}', function (string $uuid) {
    $document = config('sop_demo.documents')[$uuid] ?? null;
    abort_unless($document, 404);

    return view('pengelolaan.verifikasi', [
        'document' => $document,
        'uuid' => $uuid,
        'label' => 'Verifikasi Dokumen',
        'section' => 'Verifikasi Dokumen',
    ]);
})->whereUuid('uuid')->name('verifikasi.publik');

if (app()->environment('local', 'testing')) {
    Route::middleware(UseSimulationDatabase::class)->prefix('simulasi')->name('sim.')->controller(SimulationController::class)->group(function () {
        Route::post('/masuk', 'login')->middleware('throttle:15,1')->name('login');
        Route::post('/keluar', 'logout')->name('logout');
        Route::get('/', 'index')->name('index');
        Route::post('/sop', 'create')->name('create');
        Route::prefix('sop/{sop}')->whereUuid('sop')->group(function () {
            Route::get('/', 'show')->name('show');
            Route::put('/draf', 'save')->name('save');
            Route::post('/revisi', 'revise')->name('revise');
            Route::post('/periksa', 'review')->name('review');
            Route::post('/finalisasi', 'finalize')->middleware('throttle:15,1')->name('finalize');
            Route::get('/pdf', 'pdf')->name('pdf');
        });
    });

    Route::prefix('pengelolaan/simpanan-draf')->name('draft-backups.')->group(function () {
        Route::get('/', [DraftBackupController::class, 'index'])->name('index');
        Route::get('/{key}', [DraftBackupController::class, 'show'])->where('key', '[A-Za-z0-9_-]{1,80}')->name('show');
        Route::put('/{key}', [DraftBackupController::class, 'save'])->where('key', '[A-Za-z0-9_-]{1,80}')->name('save');
    });
}

$dedicatedRoutes = [
    'pemeriksaan.antrean-supervisor',
    'pengelolaan.draf-sop',
    'pengelolaan.dalam-pengajuan',
    'pengelolaan.perlu-revisi',
];

foreach (config('sidebar') as $section => $items) {
    foreach ($items as $label => $name) {
        if (in_array($name, $dedicatedRoutes, true)) {
            continue;
        }

        $view = match ($section) {
            'Pustaka SOP' => 'pengelolaan.pustaka',
            'Verifikasi Dokumen' => 'pengelolaan.verifikasi',
            default => 'sidebar-page',
        };

        Route::view('/'.str_replace('.', '/', $name), $view, [
            'section' => $section,
            'label' => $label,
        ])->name($name);
    }
}
