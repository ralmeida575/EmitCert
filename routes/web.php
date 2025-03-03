<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ControllerCert;

Route::get('/', function () {
    return view('index');
})->middleware('auth');

Route::get('/verificar_certificado/{hash}', [ControllerCert::class, 'validarCertificado']);

Route::post('/gerar-certificados', [ControllerCert::class, 'gerarCertificados'])
    ->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::get('/certificado/{hash}/download', [ControllerCert::class, 'download'])->name('certificados.download');

use App\Http\Controllers\CertificadoController; // Certifique-se de que o caminho do controlador esteja correto

Route::post('/webhook/certificados-processados', [ControllerCert::class, 'receberWebhook']);


require __DIR__.'/auth.php';
