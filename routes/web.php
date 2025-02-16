<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ControllerCert;

Route::get('/', function () {
return view('index');
});

// Página de verificação do certificado
Route::get('/verificar_certificado/{hash}', [ControllerCert::class, 'validarCertificado']);

// Página para gerar certificados (não precisa de autenticação)
Route::post('/gerar-certificados', [ControllerCert::class, 'gerarCertificados'])->withoutMiddleware('auth');

// Se necessário, adicione a proteção de middleware de autenticação para outras rotas
Route::middleware(['auth'])->group(function () {
Route::get('/dashboard', function() {
return view('dashboard'); // Exemplo de dashboard protegido por login
});
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

