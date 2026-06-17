<?php

declare(strict_types=1);

namespace App\Services\MovieApiProviders;

use App\Exceptions\MovieApiException;
use App\Exceptions\MovieNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OmdbProvider implements MovieApiProvidersInterface
{
    private const string BASE_URL = 'https://www.omdbapi.com';

    public function __construct(private string $apiKey) {}

    public function findByImdbId(string $imdbId): array
    {
        return $this->request(['i' => $imdbId]);
    }

    public function findByTitle(string $title): array
    {
        return $this->request(['t' => $title]);
    }

    private function request(array $params): array
    {
        try {
            $response = Http::timeout(10)->get(self::BASE_URL, array_merge($params, [
                'apikey' => $this->apiKey,
            ]));
        } catch (ConnectionException) {
            throw new MovieApiException('Movie service is unavailable. Please try again later.');
        }

        if ($response->failed()) {
            throw new MovieApiException('Movie service returned an unexpected error.');
        }

        $data = $response->json();

        if (($data['Response'] ?? 'False') === 'False') {
            $error = $data['Error'] ?? 'Movie not found.';

            if (str_contains(strtolower($error), 'limit')) {
                throw new MovieApiException('Movie service daily request limit reached. Please try again tomorrow.');
            }

            throw new MovieNotFoundException($error);
        }

        return $this->normalize($data);
    }

    private function normalize(array $data): array
    {
        return [
            'imdb_id'     => $data['imdbID'],
            'title'       => $data['Title'],
            'year'        => $data['Year'] !== 'N/A' ? $data['Year'] : null,
            'genre'       => $data['Genre'] !== 'N/A' ? $data['Genre'] : null,
            'director'    => $data['Director'] !== 'N/A' ? $data['Director'] : null,
            'overview'    => $data['Plot'] !== 'N/A' ? $data['Plot'] : null,
            'poster_url'  => isset($data['Poster']) && $data['Poster'] !== 'N/A' ? $data['Poster'] : null,
            'runtime'     => isset($data['Runtime']) && $data['Runtime'] !== 'N/A'
                ? (int) $data['Runtime']
                : null,
            'imdb_rating' => isset($data['imdbRating']) && $data['imdbRating'] !== 'N/A'
                ? (float) $data['imdbRating']
                : null,
        ];
    }
}
