<?php

use App\Http\Controllers\AccountSessionController;
use App\Http\Controllers\DraftBackupController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePagePermissionController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserImpersonationController;
use App\Http\Middleware\EnsurePageAccess;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\UseSimulationDatabase;
use Illuminate\Support\Facades\Route;

Route::view('/', 'login')->name('login');
Route::view('/masuk', 'login')->name('account.login');
Route::post('/masuk', [AccountSessionController::class, 'store'])->middleware('throttle:15,1')->name('account.login.store');
Route::view('/dashboard', 'dashboard')->name('dashboard');

Route::middleware('auth:web')->group(function () {
    Route::post('/keluar', [AccountSessionController::class, 'destroy'])->name('account.logout');
    Route::prefix('administrasi/pengguna')->name('admin.users.')->middleware(EnsureSuperAdmin::class)
        ->controller(UserController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/tambah', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{user}/edit', 'edit')->whereUuid('user')->name('edit');
            Route::patch('/{user}', 'update')->whereUuid('user')->name('update');
            Route::delete('/{user}', 'destroy')->whereUuid('user')->name('destroy');
        });
    Route::post('/administrasi/pengguna/{user}/masuk', [UserImpersonationController::class, 'store'])
        ->whereUuid('user')->middleware(EnsureSuperAdmin::class)->name('admin.impersonation.store');
    Route::middleware(EnsureSuperAdmin::class)->group(function () {
        Route::get('/administrasi/matriks-izin', [RolePagePermissionController::class, 'index'])->name('admin.users.permissions.index');
        Route::put('/administrasi/matriks-izin/{role}', [RolePagePermissionController::class, 'update'])
            ->whereUuid('role')->name('admin.users.permissions.update');
        Route::get('/administrasi/peran', [RoleController::class, 'index'])->name('admin.roles.index');
        Route::post('/administrasi/peran', [RoleController::class, 'store'])->name('admin.roles.store');
        Route::delete('/administrasi/peran/{role}', [RoleController::class, 'destroy'])
            ->whereUuid('role')->name('admin.roles.destroy');
    });
    Route::delete('/impersonasi', [UserImpersonationController::class, 'destroy'])->name('admin.impersonation.destroy');
});

$pageMiddleware = ['auth:web', EnsurePageAccess::class];

Route::middleware($pageMiddleware)->group(function () {
    Route::view('/pengelolaan/draf-sop', 'pengelolaan.draf-sop')->name('pengelolaan.draf-sop');
    Route::view('/pengelolaan/dalam-pengajuan', 'pengelolaan.dalam-pengajuan')->name('pengelolaan.dalam-pengajuan');
    Route::view('/pengelolaan/perlu-revisi', 'pengelolaan.perlu-revisi')->name('pengelolaan.perlu-revisi');
    Route::view('/pemeriksaan/antrean-supervisor', 'pengelolaan.dalam-pengajuan', ['supervisor' => true])
        ->name('pemeriksaan.antrean-supervisor');
});

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
    Route::post('/simulasi/masuk', [AccountSessionController::class, 'store'])->middleware('throttle:15,1')->name('sim.login');
    Route::middleware(UseSimulationDatabase::class)->prefix('simulasi')->name('sim.')->controller(SimulationController::class)->group(function () {
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

    Route::middleware(['auth:web', EnsurePageAccess::class.':pengelolaan.draf-sop'])
        ->prefix('pengelolaan/simpanan-draf')->name('draft-backups.')->group(function () {
            Route::get('/', [DraftBackupController::class, 'index'])->name('index');
            Route::get('/{key}', [DraftBackupController::class, 'show'])->where('key', '[A-Za-z0-9_-]{1,80}')->name('show');
            Route::put('/{key}', [DraftBackupController::class, 'save'])->where('key', '[A-Za-z0-9_-]{1,80}')->name('save');
        });
}

$dedicatedRoutes = [
    'admin.users.index',
    'admin.users.permissions.index',
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
        ])->middleware($pageMiddleware)->name($name);
    }
}
