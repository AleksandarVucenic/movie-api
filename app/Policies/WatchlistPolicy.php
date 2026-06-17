<?php

declare(strict_types=1);

namespace App\Policies;


use App\Models\User;
use App\Models\Watchlist;

class WatchlistPolicy
{
    public function view(User $user, Watchlist $item): bool
    {
        return $user->id === $item->user_id;
    }

    public function update(User $user, Watchlist $item): bool
    {
        return $user->id === $item->user_id;
    }

    public function delete(User $user, Watchlist $item): bool
    {
        return $user->id === $item->user_id;
    }
}
