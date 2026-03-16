<?php

namespace App\Notifications\Settings;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $fileName;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('settings.data.download-export', ['fileName' => $this->fileName]);

        return (new MailMessage)
                    ->subject('Votre export de données est prêt')
                    ->line('L\'exportation complète de vos données (clients, devis, factures et PDFs) est terminée.')
                    ->action('Télécharger l\'archive ZIP', $url)
                    ->line('Ce lien expirera dans 24 heures par mesure de sécurité.')
                    ->line('Merci d\'utiliser notre application !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Votre export de données est prêt.',
            'fileName' => $this->fileName,
            'url' => route('settings.data.download-export', ['fileName' => $this->fileName]),
        ];
    }
}