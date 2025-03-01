<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CertificadoEnviado extends Mailable
{
    use Queueable, SerializesModels;

    public $nome;
    public $curso;
    public $certificadoPath;
    public $hash;

    public function __construct($nome, $curso, $certificadoPath, $hash)
    {
        $this->nome = $nome;
        $this->curso = $curso;
        $this->certificadoPath = $certificadoPath;
        $this->hash = $hash;
    }

    public function build()
    {
        return $this->subject('Seu Certificado de Conclusão')
                    ->view('emails', ['hash' => $this->hash]) 
                    ->attach($this->certificadoPath);
    }
}
