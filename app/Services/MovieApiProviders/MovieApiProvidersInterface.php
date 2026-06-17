<?php

declare(strict_types=1);

namespace App\Services\MovieApiProviders;

use App\Contracts\MovieApiContract;
use App\Exceptions\MovieNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

interface MovieApiProvidersInterface {
    public function findByImdbId(string $imdbId): array;
    public function findByTitle(string $title): array;
}
