<?php

namespace App\Policies;

use App\Models\PriceAlert;
use App\Models\User;

class PriceAlertPolicy
{
    public function view(User $user, PriceAlert $alerta): bool
    {
        return $user->id === $alerta->user_id;
    }

    public function update(User $user, PriceAlert $alerta): bool
    {
        return $user->id === $alerta->user_id;
    }

    public function delete(User $user, PriceAlert $alerta): bool
    {
        return $user->id === $alerta->user_id;
    }
}
