<?php

namespace App\Notifications;

use App\Enums\QuoteStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class QuoteStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $quoteNumber,
        public int $quoteId,
        public QuoteStatus $oldStatus,
        public QuoteStatus $newStatus
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
        $color = match ($this->newStatus) {
            QuoteStatus::Approuvé => 'green',
            QuoteStatus::Rejeté => 'red',
            QuoteStatus::Expiré => 'orange',
            default => 'blue',
        };

        return [
            'type' => 'quote_status_changed',
            'quote_number' => $this->quoteNumber,
            'quote_id' => $this->quoteId,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'message' => "Le devis {$this->quoteNumber} est passé à l'état : {$this->newStatus->value}",
            'icon' => 'document-text',
            'color' => $color,
        ];
    }
}
