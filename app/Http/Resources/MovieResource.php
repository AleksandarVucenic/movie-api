<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'imdb_id'     => $this->imdb_id,
            'title'       => $this->title,
            'year'        => $this->year,
            'genre'       => $this->genre,
            'director'    => $this->director,
            'overview'    => $this->overview,
            'poster_url'  => $this->poster_url,
            'runtime'     => $this->runtime,
            'imdb_rating' => $this->imdb_rating,
        ];
    }
}
