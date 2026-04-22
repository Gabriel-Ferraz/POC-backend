<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ["mail"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = config("app.frontend_url") . "/redefinir-senha?token=" . $this->token . "&email=" . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject("Redefinir Senha - " . config("app.name"))
            ->greeting("Olá, " . $notifiable->name . "!")
            ->line("Você está recebendo este email porque recebemos uma solicitação de redefinição de senha para sua conta.")
            ->action("Redefinir Senha", $resetUrl)
            ->line("Este link de redefinição de senha expirará em 60 minutos.")
            ->line("Se você não solicitou a redefinição de senha, nenhuma ação adicional é necessária.")
            ->salutation("Atenciosamente, Equipe " . config("app.name"));
    }

    public function toArray(object $notifiable): array
    {
        return ["token" => $this->token];
    }
}
