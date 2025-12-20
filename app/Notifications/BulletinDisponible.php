<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Bulletin;

class BulletinDisponible extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Bulletin $bulletin
    ) {}

    public function via($notifiable)
    {
        return ['database', 'mail']; // ou juste 'database' si pas d'email
    }

    public function toDatabase($notifiable)
    {
        $numero = $this->bulletin->semestre?->numero ?? '';
        
        return [
            'titre' => 'Nouveau Bulletin Disponible',
            'message' => "Votre bulletin du semestre $numero est désormais consultable.",
            'bulletin_id' => $this->bulletin->id,
            'type' => 'bulletin_genere',
            'action_url' => "/etudiant/bulletins/{$this->bulletin->id}",
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Nouveau bulletin disponible')
                    ->greeting("Bonjour {$notifiable->etudiant->nom_complet},")
                    ->line("Votre bulletin du semestre {$this->bulletin->semestre?->numero} a été généré.")
                    ->action('Voir mon bulletin', url("/etudiant/bulletins/{$this->bulletin->id}"))
                    ->line('Merci de votre attention.');
    }
}