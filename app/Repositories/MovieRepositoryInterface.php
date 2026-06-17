<?php

namespace App\Repositories;

use App\Models\Movie;

interface MovieRepositoryInterface
{
    public function findOrCreateFromApiData(array $data): Movie;
}
