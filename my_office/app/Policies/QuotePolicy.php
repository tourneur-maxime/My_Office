<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuotePolicy
{
    /**
     * Determine whether the user can view the quote.
     */
    public function view(User $user, Quote $quote): Response
    {
        return $user->id === $quote->user_id
            ? Response::allow()
            : Response::deny('Vous n\'êtes pas autorisé à voir ce devis.');
    }

    /**
     * Determine whether the user can update the quote.
     */
    public function update(User $user, Quote $quote): Response
    {
        return $user->id === $quote->user_id
            ? Response::allow()
            : Response::deny('Vous n\'êtes pas autorisé à modifier ce devis.');
    }

    /**
     * Determine whether the user can delete the quote.
     */
    public function delete(User $user, Quote $quote): Response
    {
        return $user->id === $quote->user_id
            ? Response::allow()
            : Response::deny('Vous n\'êtes pas autorisé à supprimer ce devis.');
    }
}
