<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'GuestController@index')->name('landing-page');

Route::get('/berita', function () {
    return view('berita.index');
});
Route::get('/detail', function () {
    return view('berita.detail');
});



Route::get('/berandalpk', 'HomeController@berandalpk');

Route::get('login', [AuthController::class, 'show_login_form'])->name('login');
Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::get('pengelola-gudang-info', 'UserInfoController@jsonInformasiUntukPengelolaGudang')->name('json.pengelola.gudang.info');

Route::get('beranda', [HomeController::class, 'beranda'])->name('beranda');

Route::resource('komoditas', KomoditasController::class);
Route::post('tolak-komoditas/{id}', 'KomoditasController@tolakKomoditas')->name('komoditas.tolak');
Route::post('komoditas-penggudangan', 'KomoditasController@penggudangan')->name('komoditas.penggudangan');
Route::get('komoditas/get-detail-kategori/{id}', 'KomoditasController@getDetailKategoriKomoditas');
Route::get('komoditas/cetak-surat-mutu/{id}', 'KomoditasController@cetakSuratKualitas');
Route::get('json-komoditas', 'KomoditasController@jsonKomoditas')->name('json.komoditas');
Route::get('json-komoditas/{id}', 'KomoditasController@getKomoditasById');
Route::post('uji-kualitas-komoditas/{id}', 'KomoditasController@ujiKualitas')->name('komoditas.uji');
Route::post('komoditas-terjual/{id}', 'KomoditasController@komoditasTerjual')->name('komoditas.jual');

Route::get('statistik-petani', 'GuestController@indexStatistik')->name('statistik.index');
Route::post('statistik-petani', 'GuestController@showStatistik')->name('statistik.show');
Route::get('statistik-desa', 'GuestController@indexStatistikDesa')->name('statistik-desa.index');
Route::post('statistik-desa', 'GuestController@showStatistikDesa')->name('statistik-desa.show');

Route::resource('petani', UserInfoController::class);
Route::get('json-petani', 'UserInfoController@jsonPetani')->name('json.petani');
Route::get('json-petani/{id}', 'UserInfoController@jsonPetaniDetail')->name('json.petani.detail');
Route::get('get-petani-by-desa/{id}', 'UserInfoController@getPetaniByDesa')->name('get-petani-by-desa');

Route::resource('kelompok-tani', KelompokTaniController::class);
Route::get('json-kelompok-tani', 'KelompokTaniController@jsonKelompokTani')->name('json.kelompok.tani');
Route::get('json-kelompok-tani/{id}', 'KelompokTaniController@jsonKelompokTaniDetail')->name('json.kelompok.tani.detail');
Route::get('json-kelompok-tani-by-desa/{id}', 'KelompokTaniController@jsonKelompokTaniByDesa')->name('json.kelompok.tani.by.desa');
Route::get('get-kelompok-tani-by-desa/{id}', 'KelompokTaniController@getKelompokTaniByDesa')->name('get-kelompok-tani-by-desa');

Route::resource('gudang', GudangController::class);
Route::get('json-gudang', 'GudangController@jsonGudang')->name('json.gudang');
Route::get('json-gudang/{id}', 'GudangController@jsonGudangDetail')->name('json.gudang.detail');
Route::get('json-gudang-by-desa/{id}', 'GudangController@jsonGudangByDesa')->name('json.gudang.by.desa');
Route::post('gudang/manage-komoditas', 'KomoditasController@manage')->name('gudang.manage');

Route::resource('kecamatan', KecamatanController::class);
Route::get('json-kecamatan', 'KecamatanController@jsonKecamatan')->name('json.kecamatan');
Route::get('json-desa-by-kecamatan/{id}', 'KecamatanController@jsonDesaByKecamatan')->name('json.desa.by.kecamatan');

Route::post('petani-create', 'UserController@createPetani')->name('petani.create');
Route::post('petani-reset-password/{id}', 'UserController@resetPassword')->name('petani.reset-password');
Route::get('akun', 'UserController@showPetani')->name('petani.detail');
Route::put('akun/{id}', 'UserController@updatePetani')->name('petani.update');

Route::resource('desa', DesaController::class);
Route::get('json-desa', 'DesaController@jsonDesa')->name('json.desa');
Route::get('get-desa-by-kecamatan/{id}', 'DesaController@getDesaByKecamatan')->name('get-desa-by-kecamatan');

// Route::resource('jenis-cabai', KategoriKomoditasDetailController::class);
Route::get('json-jenis-cabai', 'KategoriKomoditasDetailController@jsonJenisCabai')->name('json.jenis.cabai');

Route::get('gudang-desa/{desa_id}', 'GudangController@getGudangByDesa')->name('gudang.desa');

Route::get('tes', 'KomoditasController@statistik');