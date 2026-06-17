<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WatchlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'status'     => $this->status?->value,
            'rating'     => $this->rating,
            'notes'      => $this->notes,
            'added_at'   => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'movie'      => [
                'imdb_id'     => $this->movie->imdb_id,
                'title'       => $this->movie->title,
                'year'        => $this->movie->year,
                'genre'       => $this->movie->genre,
                'director'    => $this->movie->director,
                'overview'    => $this->movie->overview,
                'poster_url'  => $this->movie->poster_url,
                'runtime'     => $this->movie->runtime,
                'imdb_rating' => $this->movie->imdb_rating,
            ],
        ];
    }
}
