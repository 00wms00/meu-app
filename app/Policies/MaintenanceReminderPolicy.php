<?php

namespace App\Policies;

use App\Models\MaintenanceReminder;
use App\Models\User;

class MaintenanceReminderPolicy
{
    public function update(User $user, MaintenanceReminder $reminder): bool
    {
        return $reminder->user_id === $user->id;
    }

    public function delete(User $user, MaintenanceReminder $reminder): bool
    {
        return $reminder->user_id === $user->id;
    }
}
