<?php

namespace App\Notifications\Settings;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportCompleteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $result;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $result)
    {
        $this->result = $result;
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
        return (new MailMessage)
                    ->subject('Importation des clients terminée')
                    ->line('Votre importation de clients est terminée.')
                    ->line('Succès : ' . $this->result['success_count'])
                    ->line('Échecs : ' . $this->result['failed_count'])
                    ->action('Voir vos clients', route('clients.index'))
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
            'message' => 'L\'importation des clients est terminée.',
            'success_count' => $this->result['success_count'],
            'failed_count' => $this->result['failed_count'],
            'errors' => $this->result['errors'],
        ];
    }
}