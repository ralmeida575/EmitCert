<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ControllerCert;

// Página inicial protegida por autenticação
Route::get('/', function () {
    return view('index');
})->middleware('auth');

// Página de verificação do certificado (pública)
Route::get('/verificar_certificado/{hash}', [ControllerCert::class, 'validarCertificado']);

// Página para gerar certificados (protegida por autenticação)
Route::post('/gerar-certificados', [ControllerCert::class, 'gerarCertificados'])
    ->middleware('auth');

// Rotas autenticadas para perfil
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
