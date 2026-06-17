<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Watchlist\WatchlistStatus;
use App\Filters\QueryFilter;
use App\Models\User;
use App\Models\Watchlist;
use App\Repositories\MovieRepository;
use App\Services\MovieApiProviders\MovieApiProvidersInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class WatchlistService
{
    public function __construct(
        private readonly MovieApiProvidersInterface $movieApi,
        private MovieRepository $movies,
    ) {
    }

    public function list(User $user, QueryFilter $queryFilter, int $perPage = 15): LengthAwarePaginator
    {
        return $user
            ->watchlist()
            ->filter($queryFilter)
            ->paginate($perPage);
    }

    public function addMovie(User $user, ?string $imdbId, ?string $title): Watchlist
    {
        $movieData = $imdbId
            ? $this->movieApi->findByImdbId($imdbId)
            : $this->movieApi->findByTitle($title);

        $movie = $this->movies->findOrCreateFromApiData($movieData);

        return $user->watchlist()->create([
            'movie_id' => $movie->id,
            'status'   => WatchlistStatus::ToWatch,
        ]);
    }
}
