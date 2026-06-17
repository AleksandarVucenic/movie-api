<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\MovieApiException;
use App\Exceptions\MovieNotFoundException;
use App\Filters\WatchlistFilter;
use App\Http\Requests\Watchlist\StoreWatchlistRequest;
use App\Http\Requests\Watchlist\UpdateWatchlistRequest;
use App\Http\Requests\Watchlist\WatchlistRequest;
use App\Http\Resources\WatchlistResource;
use App\Models\Watchlist;
use App\Services\WatchlistService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WatchlistController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly WatchlistService $watchlist) {}

    public function index(WatchlistRequest $request, WatchlistFilter $filter): AnonymousResourceCollection
    {
        $items = $this->watchlist->list($request->user(), $filter, $request->integer('per_page', 15));

        return WatchlistResource::collection($items);
    }

    public function store(StoreWatchlistRequest $request)
    {
        try {
            $item = $this->watchlist->addMovie(
                $request->user(),
                $request->input('imdb_id'),
                $request->input('title'),
            );
        } catch (MovieNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (MovieApiException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'This movie is already in your watchlist.'], 409);
        }

        return new WatchlistResource($item->load('movie'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Watchlist $watchlist): WatchlistResource
    {
        $this->authorize('view', $watchlist);

        return new WatchlistResource($watchlist->load('movie'));
    }

    public function update(UpdateWatchlistRequest $request, Watchlist $watchlist): WatchlistResource
    {
        $this->authorize('update', $watchlist);

        $watchlist->update($request->validated());

        return new WatchlistResource($watchlist->load('movie'));
    }

    public function destroy(Watchlist $watchlist): JsonResponse
    {
        $this->authorize('delete', $watchlist);

        $watchlist->delete();

        return response()->json(null, 204);
    }
}
