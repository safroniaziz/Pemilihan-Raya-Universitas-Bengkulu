<?php

use App\Models\Jadwal;
use App\Models\Kandidat;
use App\Models\Rekapitulasi;
use Illuminate\Support\Facades\DB;
use App\Livewire\QuickCountLivewire;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DptController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PandaController;
use App\Http\Controllers\CekDptController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KandidatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RekapitulasiController;
use App\Http\Controllers\JadwalKegiatanController;
use App\Http\Controllers\DashboardPemilihController;
use App\Http\Controllers\StatistikController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [DashboardPemilihController::class, 'welcome'])->name('welcome');
Route::get('/kandidat-presma', [DashboardPemilihController::class, 'guestKandidat'])->name('guest.kandidat');
Route::get('/{kandidat}/visimisi', [DashboardPemilihController::class, 'guestVisiMisi'])->name('visimisi');

Route::get('/cek_dpt', [CekDptController::class, 'cekDpt'])
    ->name('cekDpt');
Route::post('/cek_status_dpt', [CekDptController::class, 'cekStatusDpt'])->name('cek_status_dpt');


Route::get('/dashboard', [DashboardController::class, 'dashboard'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/masuk', [PandaController::class, 'showLoginForm'])->name('panda.login');
Route::post('/pandalogin', [PandaController::class, 'pandaLogin'])->name('panda.login.post');
Route::get('/logout', [PandaController::class, 'pandaLogout'])->name('panda.logout');


// Route::get('/verifikasi-data', [DashboardPemilihController::class, 'verifikasiData'])->name('mahasiswa.verifikasi');
Route::get('/rekaputulasi', QuickCountLivewire::class)->middleware(['auth', 'web'])->name('rekapitulasi_suara');

Route::group(['prefix' => 'mahasiswa', 'middleware' => 'isPandaLogin'], function () {
    Route::get('/dashboard', [DashboardPemilihController::class, 'dashboard'])->name('mahasiswa.dashboard');
    Route::get('/pilih-kandidat', [DashboardPemilihController::class, 'voting'])->name('mahasiswa.voting');
    Route::get('/{kandidat}/pilih', [DashboardPemilihController::class, 'pemilihPost'])->name('mahasiswa.pilih');
    Route::get('/{kandidat}/visi-misi-kandidat', [DashboardPemilihController::class, 'visiMisi'])->name('mahasiswa.visi-misi');
});

Route::controller(KandidatController::class)->middleware(['auth', 'web'])->prefix('/kandidat')->group(function () {
    Route::get('/', 'index')->name('kandidat');
    Route::get('/create', 'create')->name('kandidat.create');
    Route::post('/', 'store')->name('kandidat.store');
    Route::get('/{kandidat}/edit', 'edit')->name('kandidat.edit');
    Route::patch('/{kandidat}/edit', 'update')->name('kandidat.update');
    Route::delete('/{kandidat}/delete', 'destroy')->name('kandidat.destroy');
    Route::get('/{kandidat}/create_misi', 'createMisi')->name('kandidat.createMisi');
    Route::get('/{kandidat}/detail_misi', 'detailMisi')->name('kandidat.detailMisi');
    Route::get('/{misi}/editMisi', 'editMisi')->name('kandidat.editMisi');
    Route::delete('/{misi}/deleteMisi', 'destroyMisi')->name('kandidat.destroyMisi');
    Route::post('/{kandidat}/store_misi', 'storeMisi')->name('kandidat.storeMisi');
    Route::post('kandidat/{kandidat}/misi/{misi}/edit_misi', 'storeEditMisi')->name('kandidat.storeEditMisi');
});

Route::controller(ContactController::class)->middleware(['auth', 'web'])->prefix('/contact')->group(function () {
    Route::get('/', 'index')->name('contact');
    Route::get('/create', 'create')->name('contact.create');
    Route::post('/', 'store')->name('contact.store');
    Route::get('/{contact}/edit', 'edit')->name('contact.edit');
    Route::patch('/{contact}/edit', 'update')->name('contact.update');
    Route::delete('/{contact}/delete', 'destroy')->name('contact.destroy');
});

Route::controller(JadwalKegiatanController::class)->middleware(['auth', 'web'])->prefix('/jadwal_kegiatan')->group(function () {
    Route::get('/', 'index')->name('jadwal_kegiatan');
    Route::get('/create', 'create')->name('jadwal_kegiatan.create');
    Route::post('/', 'store')->name('jadwal_kegiatan.store');
    Route::get('/{jadwal_kegiatan}/edit', 'edit')->name('jadwal_kegiatan.edit');
    Route::patch('/{jadwal_kegiatan}/edit', 'update')->name('jadwal_kegiatan.update');
    Route::delete('/{jadwal_kegiatan}/delete', 'destroy')->name('jadwal_kegiatan.destroy');
});

Route::controller(DptController::class)->middleware(['auth', 'web'])->prefix('/data-dpt')->group(function () {
    Route::get('/', 'index')->name('dpt');
    Route::get('/create', 'create')->name('dpt.create');
    Route::post('/', 'store')->name('dpt.store');
    Route::get('/{dpt}/edit-dpt', 'edit')->name('dpt.edit');
    Route::patch('/{dpt}/edit', 'update')->name('dpt.update');
    Route::delete('/{dpt}/delete', 'destroy')->name('dpt.destroy');
    Route::get('/cari', 'dptCari')->name('dpt.cari');
    Route::delete('/dpt/delete-all', 'deleteAllDpts')->name('delete-all-dpts');
});
Route::post('/dpt/import', [DptController::class, 'import'])->middleware(['auth', 'web'])->name('dpt.import');

Route::controller(UserController::class)->middleware(['auth', 'web'])->prefix('/user')->group(function () {
    Route::get('/', 'index')->name('user');
    Route::get('/create', 'create')->name('user.create');
    Route::post('/', 'store')->name('user.store');
    Route::get('/{user}/edit', 'edit')->name('user.edit');
    Route::patch('/{user}/edit', 'update')->name('user.update');
    Route::delete('/{user}/delete', 'destroy')->name('user.destroy');
    Route::patch('/', 'updatePassword')->name('user.updatePassword');
});

Route::controller(JadwalController::class)->middleware(['auth', 'web'])->prefix('/jadwal')->group(function () {
    Route::get('/', 'index')->name('jadwal');
    Route::patch('/{jadwal}/update', 'update')->name('jadwal.update');
});

Route::controller(RekapitulasiController::class)->middleware(['auth', 'web'])->prefix('/rekapitulasi')->group(function () {
    Route::get('/', 'index')->name('rekapitulasi');
    Route::get('/_cari', 'rekapitulasiCari')->name('rekapitulasi.cari');
    Route::get('/download-excel', 'downloadExcel')->name('downloadExcel');
    Route::get('/reset-rekapitulasi-suara', 'resetRekapSuara')->name('reset_rekapitulasi_suara');
    Route::delete('/reset-rekapitulasi-suara-destroy', 'RekapSuaraDestroy')->name('rekapitulasi.destroy');

});

Route::controller(StatistikController::class)->middleware(['auth', 'web'])->prefix('/statistik')->group(function () {
    Route::get('/', 'index')->name('statistik');
    Route::get('/cari', 'cari')->name('statistik.cari');
});

require __DIR__ . '/auth.php';
