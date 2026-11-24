<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Loan $loan): bool
    {
        if ($user->esAdmin()) {
            return true;
        }

        return $user->reader !== null && (int) $loan->reader_id === (int) $user->reader->id;
    }

    public function create(User $user): bool
    {
        return $user->esAdmin() || ($user->reader !== null && $user->reader->estado === 'activo');
    }

    public function returnBook(User $user, Loan $loan): bool
    {
        if ($user->esAdmin()) {
            return true;
        }

        return $user->reader !== null
            && (int) $loan->reader_id === (int) $user->reader->id
            && $loan->esSoloDigital();
    }

    public function renew(User $user, Loan $loan): bool
    {
        if ($user->esAdmin()) {
            return true;
        }

        return $user->reader !== null
            && (int) $loan->reader_id === (int) $user->reader->id
            && $user->reader->estado === 'activo';
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $user->esAdmin();
    }
}
