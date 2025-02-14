<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ControllerCert;

Route::get('/', function () {
    return view('index'); // Página inicial
});

// Rota para verificar certificados
Route::get('/verificar-certificado/{certificado}', [ControllerCert::class, 'verificarCertificado']);

// Rota para gerar certificados
Route::post('/gerar_certificados', [ControllerCert::class, 'gerarCertificados']);

// Rota para exibir a lista de certificados gerados
Route::get('/certificados', [ControllerCert::class, 'exibirCertificados']);
