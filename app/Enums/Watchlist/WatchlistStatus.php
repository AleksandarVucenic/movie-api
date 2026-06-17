<?php

declare(strict_types=1);

namespace App\Enums\Watchlist;

enum WatchlistStatus: string
{
    case ToWatch = 'to_watch';
    case Watching = 'watching';
    case Watched = 'watched';
}
