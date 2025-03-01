<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;

class CustomResetPasswordNotification extends Notification
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = $this->resetUrl($notifiable);
        return (new MailMessage)
            ->subject('Seu link para reset de senha')
            ->greeting('Olá!')
            ->line('Você está recebendo este e-mail porque foi solicitado um reset de senha para sua conta.')
            ->action('Resetar Senha', $url)
            ->line('Este link expirará em 60 minutos.')
            ->line('Se você não solicitou o reset de senha, nenhuma ação é necessária.')
            ->salutation('Atenciosamente, Laravel');
    }

    protected function resetUrl($notifiable)
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
