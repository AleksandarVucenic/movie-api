<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    protected $fillable = [
        'imdb_id',
        'title',
        'year',
        'genre',
        'director',
        'overview',
        'poster_url',
        'runtime',
        'imdb_rating',
    ];

    public function watchlist(): HasMany
    {
        return $this->hasMany(Watchlist::class);
    }
}
