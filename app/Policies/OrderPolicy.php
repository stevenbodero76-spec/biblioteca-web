<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return $user->esAdmin() || (int) $order->user_id === (int) $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->esAdmin();
    }
}
