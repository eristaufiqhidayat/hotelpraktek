<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrontOfficeController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\HousekeepingController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(session('pms_user') ? 'dashboard' : 'login'));
Route::get('/masuk', [AuthController::class, 'show'])->name('login');
Route::post('/masuk', [AuthController::class, 'login'])->name('login.do');
Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

Route::middleware('pms')->group(function () {
    Route::get('/beranda', DashboardController::class)->name('dashboard');

    // Reservasi
    Route::get('/reservasi', [ReservationController::class, 'index'])->name('res.index');
    Route::post('/reservasi', [ReservationController::class, 'store'])->name('res.store');
    Route::post('/reservasi/{reservation}/batal', [ReservationController::class, 'cancel'])->name('res.cancel');

    // Front office
    Route::get('/front-office', [FrontOfficeController::class, 'index'])->name('fo.index');
    Route::post('/reservasi/{reservation}/check-in', [FrontOfficeController::class, 'checkin'])->name('fo.checkin');
    Route::post('/reservasi/{reservation}/folio', [FrontOfficeController::class, 'post'])->name('fo.post');
    Route::post('/reservasi/{reservation}/check-out', [FrontOfficeController::class, 'checkout'])->name('fo.checkout');
    Route::post('/reservasi/{reservation}/perpanjang', [FrontOfficeController::class, 'extend'])->name('fo.extend');
    Route::get('/reservasi/{reservation}/cetak', [FrontOfficeController::class, 'printFolio'])->name('fo.print');

    // Housekeeping
    Route::get('/housekeeping', [HousekeepingController::class, 'index'])->name('hk.index');
    Route::post('/housekeeping/{room}/status', [HousekeepingController::class, 'status'])->name('hk.status');
    Route::post('/housekeeping/{room}/attendant', [HousekeepingController::class, 'attendant'])->name('hk.attendant');
    Route::post('/housekeeping/{room}/kerusakan', [HousekeepingController::class, 'issue'])->name('hk.issue');

    // Restoran (POS)
    Route::get('/restoran', [PosController::class, 'index'])->name('pos.index');
    Route::post('/restoran/keranjang', [PosController::class, 'add'])->name('pos.add');
    Route::post('/restoran/keranjang/jumlah', [PosController::class, 'qty'])->name('pos.qty');
    Route::post('/restoran/keranjang/kosongkan', [PosController::class, 'clear'])->name('pos.clear');
    Route::post('/restoran/bayar', [PosController::class, 'pay'])->name('pos.pay');

    // Night audit & laporan
    Route::get('/night-audit', [AuditController::class, 'index'])->name('audit.index');
    Route::post('/night-audit', [AuditController::class, 'run'])->name('audit.run');
    Route::get('/laporan', ReportController::class)->name('report');

    // Guru
    Route::middleware('guru')->group(function () {
        Route::get('/guru', [GuruController::class, 'index'])->name('guru');
        Route::post('/guru/skenario', [GuruController::class, 'scenario'])->name('guru.scenario');
        Route::get('/guru/log.csv', [GuruController::class, 'export'])->name('guru.export');
        Route::post('/guru/reset', [GuruController::class, 'reset'])->name('guru.reset');
        Route::get('/pengaturan', [SettingController::class, 'index'])->name('setting');
        Route::post('/pengaturan', [SettingController::class, 'save'])->name('setting.save');
        Route::post('/pengaturan/siswa', [SettingController::class, 'students'])->name('setting.students');
    });
});
