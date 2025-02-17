<?php

// app/Models/Certificado.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificado extends Model
{
    use HasFactory;

    protected $fillable = [
<<<<<<< HEAD
        'nome', 'cpf', 'curso', 'carga_horaria', 'email', 'certificado_path',
        'data_emissao', 'data_conclusao', 'qr_code_path', 'hash', 'unidade'
=======
        'nome', 'curso', 'carga_horaria', 'email', 'certificado_path', 
        'unidade', 'cpf', 'data_emissao', 'data_conclusao', 'qr_code_path', 'hash'
>>>>>>> 3fd43c9c03f8177bb0e5bff5a213da0373cbe19d
    ];
}
