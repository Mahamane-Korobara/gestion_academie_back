<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCredentialsSent extends Notification
{
    use Queueable;

    public function __construct(
        private string $temporaryPassword,
        private bool $isReactivation = false
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->isReactivation ? 'Votre compte a été réactivé' : 'Bienvenue – Vos identifiants de connexion')
            ->greeting($this->isReactivation ? 'Bonjour,' : 'Bienvenue sur la plateforme !')
            ->line('Voici vos identifiants pour vous connecter :')
            ->line("**Adresse e-mail :** {$notifiable->email}")
            ->line("**Mot de passe temporaire :** `{$this->temporaryPassword}`")
            ->line('⚠️ Vous devrez **changer ce mot de passe** lors de votre première connexion.')
            ->line('Ne partagez ces informations avec personne.')
            ->salutation("L’équipe " . config('app.name'));
    }
}