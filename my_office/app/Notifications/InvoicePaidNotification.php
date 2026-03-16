<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvoicePaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $invoiceNumber,
        public int $invoiceId,
        public float $amount
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
        $formattedAmount = number_format($this->amount, 2, ',', ' ') . ' €';

        return [
            'type' => 'invoice_paid',
            'invoice_number' => $this->invoiceNumber,
            'invoice_id' => $this->invoiceId,
            'amount' => $this->amount,
            'formatted_amount' => $formattedAmount,
            'message' => "La facture {$this->invoiceNumber} d'un montant de {$formattedAmount} a été marquée comme payée.",
            'icon' => 'cash',
            'color' => 'green',
        ];
    }
}
