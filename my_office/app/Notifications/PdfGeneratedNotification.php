<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PdfGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $documentType,
        public string $documentNumber,
        public int $documentId,
        public string $downloadRoute
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pdf_generated',
            'document_type' => $this->documentType,
            'document_number' => $this->documentNumber,
            'document_id' => $this->documentId,
            'download_route' => $this->downloadRoute,
            'message' => "Le PDF du {$this->documentType} {$this->documentNumber} a été généré avec succès.",
            'icon' => 'document',
            'color' => 'green',
        ];
    }
}
