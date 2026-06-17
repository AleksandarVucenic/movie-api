<?php

namespace App\Repositories;

use App\Models\Movie;

class MovieRepository implements MovieRepositoryInterface
{
    public function findOrCreateFromApiData(array $data): Movie
    {
        return Movie::firstOrCreate(
            ['imdb_id' => $data['imdb_id']],
            $data,
        );
    }
}
