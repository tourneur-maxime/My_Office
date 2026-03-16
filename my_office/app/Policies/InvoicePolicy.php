<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvoicePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * Only draft invoices can be updated.
     */
    public function update(User $user, Invoice $invoice): Response
    {
        if ($user->id !== $invoice->user_id) {
            return Response::deny('Action non autorisée.');
        }

        if ($invoice->status !== InvoiceStatus::Brouillon) {
            return Response::deny('Seules les factures en brouillon peuvent être modifiées.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the model.
     * Only draft invoices can be deleted.
     */
    public function delete(User $user, Invoice $invoice): Response
    {
        if ($user->id !== $invoice->user_id) {
            return Response::deny('Action non autorisée.');
        }

        if ($invoice->status !== InvoiceStatus::Brouillon) {
            return Response::deny('Seules les factures en brouillon peuvent être supprimées.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can mark the invoice as paid.
     * Cannot mark draft or cancelled invoices as paid.
     */
    public function markAsPaid(User $user, Invoice $invoice): Response
    {
        if ($user->id !== $invoice->user_id) {
            return Response::deny('Action non autorisée.');
        }

        if ($invoice->status === InvoiceStatus::Brouillon || $invoice->status === InvoiceStatus::Annulé) {
            return Response::deny('Cette facture ne peut pas être marquée comme payée.');
        }

        return Response::allow();
    }
}
