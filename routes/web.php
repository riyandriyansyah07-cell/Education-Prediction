<?php

use App\Http\Controllers\AccessLogController;
use App\Http\Controllers\AccountTicketController;
use App\Http\Controllers\SkpiDocumentController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dpa;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\Student;
use App\Http\Controllers\Validator;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'landing'])->name('landing');
Route::get('/cara-risiko-dihitung', [PageController::class, 'methodology'])->name('metodologi');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'show'])->name('login');
    Route::post('/masuk', [AuthController::class, 'store'])->name('login.store');

    // Pengaturan kata sandi awal / reset melalui tautan sekali pakai bertoken (S1)
    Route::get('/atur-kata-sandi/{token}', [PasswordController::class, 'showSetup'])
        ->middleware('throttle:10,1')
        ->name('sandi.atur.form');
    Route::post('/atur-kata-sandi', [PasswordController::class, 'completeSetup'])
        ->middleware('throttle:10,1')
        ->name('sandi.atur.store');

    // E-Ticket Pengajuan Akun Baru / Reset Sandi ke Sekretariat (S2: rate limit diperketat)
    Route::get('/ajukan-akun', [AccountTicketController::class, 'showForm'])
        ->middleware('throttle:10,1')
        ->name('tiket.form');
    Route::post('/ajukan-akun', [AccountTicketController::class, 'submit'])
        ->middleware('throttle:5,10')
        ->name('tiket.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/beranda', [PageController::class, 'home'])->name('beranda');
    Route::post('/onboarding/tutup', [OnboardingController::class, 'dismiss'])->name('onboarding.dismiss');
    Route::get('/kata-sandi', [PasswordController::class, 'show'])->name('sandi.edit');
    Route::post('/kata-sandi', [PasswordController::class, 'update'])->name('sandi.update');

    // Mahasiswa: UC-02 sampai UC-07 + Cek Berkala Langsung, Input Cepat, & Pelacak 20 SKS Pengayaan
    Route::middleware('role:mahasiswa')->prefix('mahasiswa')->name('mhs.')->group(function () {
        Route::get('/ringkasan', [Student\SummaryController::class, 'show'])->name('ringkasan');
        Route::post('/ringkasan/hitung', [Student\SummaryController::class, 'recalculate'])->name('hitung');
        Route::post('/ringkasan/tambah-mk', [Student\SummaryController::class, 'storeManualCourses'])->name('tambah-mk');
        Route::post('/ringkasan/input-cepat', [Student\SummaryController::class, 'quickUpdate'])->middleware('throttle:10,1')->name('input-cepat');

        Route::get('/impor', [Student\ImportController::class, 'show'])->name('impor');
        Route::post('/impor', [Student\ImportController::class, 'store'])->middleware('throttle:10,1')->name('impor.store');
        Route::get('/impor/templat', [Student\ImportController::class, 'template'])->name('templat');

        Route::get('/pengayaan', [Student\EnrichmentController::class, 'index'])->name('pengayaan');
        Route::post('/pengayaan', [Student\EnrichmentController::class, 'store'])->name('pengayaan.store');
        Route::post('/pengayaan/skpi', [Student\EnrichmentController::class, 'updateSkpi'])->middleware('throttle:10,1')->name('pengayaan.skpi');
        Route::delete('/pengayaan/skpi-dokumen/{document}', [SkpiDocumentController::class, 'destroy'])->whereNumber('document')->name('pengayaan.skpi.hapus');
        Route::patch('/pengayaan/{record}/status', [Student\EnrichmentController::class, 'updateStatus'])
            ->whereNumber('record')
            ->name('pengayaan.status');
        Route::delete('/pengayaan/{record}', [Student\EnrichmentController::class, 'destroy'])
            ->whereNumber('record')
            ->name('pengayaan.destroy');

        Route::get('/privasi', [Student\PrivacyController::class, 'show'])->name('privasi');
        Route::post('/privasi/izin', [Student\PrivacyController::class, 'consent'])->name('privasi.izin');
        Route::post('/privasi/pengingat', [Student\PrivacyController::class, 'reminders'])->middleware('throttle:6,10')->name('privasi.pengingat');

        Route::get('/riwayat', [Student\HistoryController::class, 'show'])->name('riwayat');
        Route::delete('/riwayat/{semester}', [Student\HistoryController::class, 'destroy'])
            ->whereNumber('semester')
            ->name('riwayat.hapus');
        Route::get('/catatan-akses', [AccessLogController::class, 'student'])->name('catatan');
    });

    // Bukti Sertifikat SKPI (privat: pemilik, DPA pembimbing, atau Admin; verifikasi oleh DPA/Admin)
    Route::get('/skpi-dokumen/{document}', [SkpiDocumentController::class, 'show'])
        ->whereNumber('document')
        ->name('skpi.dokumen.show');
    Route::post('/skpi-dokumen/{document}/verifikasi', [SkpiDocumentController::class, 'verify'])
        ->whereNumber('document')
        ->name('skpi.dokumen.verifikasi');

    // DPA: UC-08, UC-09, UC-14 + Monitoring Bimbingan & Penilaian DPA
    Route::middleware('role:dpa')->prefix('dpa')->name('dpa.')->group(function () {
        Route::get('/bimbingan', [Dpa\GuidanceController::class, 'index'])->name('bimbingan');
        Route::get('/bimbingan/{student}', [Dpa\GuidanceController::class, 'show'])
            ->whereNumber('student')
            ->name('bimbingan.show');
        Route::post('/bimbingan/{student}/monitoring', [Dpa\GuidanceController::class, 'updateMonitoring'])
            ->whereNumber('student')
            ->name('bimbingan.monitoring');
        Route::post('/bimbingan/{student}/catatan', [Dpa\GuidanceController::class, 'storeNote'])
            ->whereNumber('student')
            ->name('bimbingan.catatan');
    });

    // Validator AI: Human-in-the-Loop Audit & Verifikasi Hasil Perhitungan AI Gemini
    Route::middleware('role:validator')->prefix('validator')->name('validator.')->group(function () {
        Route::get('/audit', [Validator\AuditController::class, 'index'])->name('audit');
        Route::post('/audit/{result}', [Validator\AuditController::class, 'store'])
            ->whereNumber('result')
            ->name('audit.store');
        Route::get('/audit/ekspor', [Validator\AuditController::class, 'export'])->name('audit.ekspor');
    });

    // Admin: Dasbor Ringkasan + UC-07, UC-10 sampai UC-12 + Persetujuan E-Ticket Akun
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dasbor', [Admin\DashboardController::class, 'index'])->name('dasbor');
        Route::get('/dasbor/ekspor', [Admin\DashboardController::class, 'export'])->name('dasbor.ekspor');

        Route::get('/akun', [Admin\AccountController::class, 'index'])->name('akun');
        Route::get('/akun/baru', [Admin\AccountController::class, 'create'])->name('akun.baru');
        Route::post('/akun', [Admin\AccountController::class, 'store'])->name('akun.store');
        Route::get('/akun/{user}/ubah', [Admin\AccountController::class, 'edit'])
            ->whereNumber('user')
            ->name('akun.ubah');
        Route::put('/akun/{user}', [Admin\AccountController::class, 'update'])
            ->whereNumber('user')
            ->name('akun.update');
        Route::delete('/akun/{user}', [Admin\AccountController::class, 'destroy'])
            ->whereNumber('user')
            ->name('akun.hapus');

        Route::get('/tiket-akun', [AccountTicketController::class, 'adminIndex'])->name('tiket');
        Route::post('/tiket-akun/{ticket}/setujui', [AccountTicketController::class, 'approve'])->whereNumber('ticket')->name('tiket.setujui');
        Route::post('/tiket-akun/{ticket}/tolak', [AccountTicketController::class, 'reject'])->whereNumber('ticket')->name('tiket.tolak');
        Route::delete('/tiket-akun/{ticket}', [AccountTicketController::class, 'destroy'])->whereNumber('ticket')->name('tiket.hapus');
        Route::delete('/tiket-akun-riwayat', [AccountTicketController::class, 'clearProcessed'])->name('tiket.bersihkan');

        Route::get('/verifikasi-skpi', [SkpiDocumentController::class, 'adminIndex'])->name('skpi');

        Route::get('/data-historis', [Admin\HistoricalDataController::class, 'index'])->name('historis');
        Route::post('/data-historis', [Admin\HistoricalDataController::class, 'store'])->name('historis.store');
        Route::delete('/data-historis/{dataset}', [Admin\HistoricalDataController::class, 'destroy'])
            ->whereNumber('dataset')
            ->name('historis.hapus');
        Route::get('/data-historis/templat', [Admin\HistoricalDataController::class, 'template'])->name('historis.templat');

        Route::get('/model', [Admin\ModelController::class, 'index'])->name('model');
        Route::post('/model/latih', [Admin\ModelController::class, 'train'])->name('model.latih');
        Route::post('/model/{version}/aktifkan', [Admin\ModelController::class, 'activate'])
            ->whereNumber('version')
            ->name('model.aktifkan');

        Route::get('/catatan-akses', [AccessLogController::class, 'admin'])->name('catatan');
    });
});