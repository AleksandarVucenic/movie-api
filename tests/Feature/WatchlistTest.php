<?php

declare(strict_types=1);

namespace Tests\Feature\Watchlist;

use App\Enums\Watchlist\WatchlistStatus;
use App\Exceptions\MovieApiException;
use App\Exceptions\MovieNotFoundException;
use App\Models\Movie;
use App\Models\User;
use App\Models\Watchlist;
use App\Services\MovieApiProviders\MovieApiProvidersInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class WatchlistTest extends TestCase
{
    use RefreshDatabase;

    private function fakeMovieData(array $overrides = []): array
    {
        return array_merge([
            'imdb_id' => 'tt1375666',
            'title' => 'Inception',
            'year' => '2010',
            'genre' => 'Action, Adventure, Sci-Fi',
            'director' => 'Christopher Nolan',
            'overview' => 'A thief who steals corporate secrets.',
            'poster_url' => 'https://example.com/inception.jpg',
            'runtime' => 148,
            'imdb_rating' => 8.8,
        ], $overrides);
    }

    private function createItem(User $user, array $movieData = [], array $itemData = []): Watchlist
    {
        $movie = Movie::create(array_merge($this->fakeMovieData(), $movieData));

        return Watchlist::create(array_merge([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'status' => WatchlistStatus::ToWatch,
        ], $itemData));
    }

    public function test_user_can_add_movie_by_imdb_id(): void
    {
        $user = User::factory()->create();
        $movieData = $this->fakeMovieData();

        $this->mock(MovieApiProvidersInterface::class, function (MockInterface $mock) use ($movieData) {
            $mock->shouldReceive('findByImdbId')
                ->once()
                ->with('tt1375666')
                ->andReturn($movieData);
        });

        $this->actingAs($user)
            ->postJson('/api/watchlist', ['imdb_id' => 'tt1375666'])
            ->assertCreated()
            ->assertJsonPath('data.movie.title', 'Inception')
            ->assertJsonPath('data.status', WatchlistStatus::ToWatch->value);

        $this->assertDatabaseHas('movies', ['imdb_id' => 'tt1375666']);
        $this->assertDatabaseHas('watchlists', ['user_id' => $user->id]);
    }

    public function test_user_can_add_movie_by_title(): void
    {
        $user = User::factory()->create();

        $this->mock(MovieApiProvidersInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByTitle')
                ->once()
                ->with('Inception')
                ->andReturn($this->fakeMovieData());
        });

        $this->actingAs($user)
            ->postJson('/api/watchlist', ['title' => 'Inception'])
            ->assertCreated()
            ->assertJsonPath('data.movie.imdb_id', 'tt1375666');
    }

    public function test_adding_movie_twice_returns_conflict(): void
    {
        $user = User::factory()->create();
        $this->createItem($user);

        $this->mock(MovieApiProvidersInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByImdbId')
                ->once()
                ->andReturn($this->fakeMovieData());
        });

        $this->actingAs($user)
            ->postJson('/api/watchlist', ['imdb_id' => 'tt1375666'])
            ->assertConflict();
    }

    public function test_returns_404_when_movie_not_found_on_omdb(): void
    {
        $user = User::factory()->create();

        $this->mock(MovieApiProvidersInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByImdbId')
                ->once()
                ->andThrow(new MovieNotFoundException('Movie not found!'));
        });

        $this->actingAs($user)
            ->postJson('/api/watchlist', ['imdb_id' => 'tt9999999'])
            ->assertNotFound();
    }

    public function test_returns_503_when_movie_api_is_unavailable(): void
    {
        $user = User::factory()->create();

        $this->mock(MovieApiProvidersInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByImdbId')
                ->once()
                ->andThrow(new MovieApiException('Movie service is unavailable. Please try again later.'));
        });

        $this->actingAs($user)
            ->postJson('/api/watchlist', ['imdb_id' => 'tt1375666'])
            ->assertStatus(503)
            ->assertJsonPath('message', 'Movie service is unavailable. Please try again later.');
    }

    public function test_store_requires_imdb_id_or_title(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/watchlist', [])
            ->assertUnprocessable();
    }

    public function test_user_can_list_their_watchlist(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->createItem($user);
        $this->createItem($other, ['imdb_id' => 'tt0000001', 'title' => 'Other Movie']);

        $response = $this->actingAs($user)->getJson('/api/watchlist');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_user_can_filter_watchlist_by_status(): void
    {
        $user = User::factory()->create();

        $this->createItem($user, [], ['status' => WatchlistStatus::Watched]);
        $this->createItem($user, ['imdb_id' => 'tt0000002', 'title' => 'Movie 2'], ['status' => WatchlistStatus::ToWatch]);

        $this->actingAs($user)
            ->getJson('/api/watchlist?status=watched')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'watched');
    }

    public function test_user_can_search_watchlist_by_title(): void
    {
        $user = User::factory()->create();
        $this->createItem($user);
        $this->createItem($user, ['imdb_id' => 'tt0000003', 'title' => 'The Dark Knight']);

        $this->actingAs($user)
            ->getJson('/api/watchlist?search=Dark')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.movie.title', 'The Dark Knight');
    }

    public function test_user_can_view_single_watchlist_item(): void
    {
        $user = User::factory()->create();
        $item = $this->createItem($user);

        $this->actingAs($user)
            ->getJson("/api/watchlist/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.movie.title', 'Inception');
    }

    public function test_user_cannot_view_other_users_item(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $item = $this->createItem($other);

        $this->actingAs($user)
            ->getJson("/api/watchlist/{$item->id}")
            ->assertForbidden();
    }

    public function test_user_can_update_watchlist_item(): void
    {
        $user = User::factory()->create();
        $item = $this->createItem($user);

        $this->actingAs($user)
            ->patchJson("/api/watchlist/{$item->id}", [
                'status' => 'watched',
                'rating' => 9,
                'notes' => 'One of my all-time favourites.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'watched')
            ->assertJsonPath('data.rating', 9);
    }

    public function test_user_cannot_update_other_users_item(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $item = $this->createItem($other);

        $this->actingAs($user)
            ->patchJson("/api/watchlist/{$item->id}", ['status' => 'watched'])
            ->assertForbidden();
    }

    public function test_user_can_delete_watchlist_item(): void
    {
        $user = User::factory()->create();
        $item = $this->createItem($user);

        $this->actingAs($user)
            ->deleteJson("/api/watchlist/{$item->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('watchlists', ['id' => $item->id]);
    }

    public function test_user_cannot_delete_other_users_item(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $item = $this->createItem($other);

        $this->actingAs($user)
            ->deleteJson("/api/watchlist/{$item->id}")
            ->assertForbidden();
    }
}
