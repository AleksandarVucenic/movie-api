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
            'status'     => $this->status->value,
            'rating'     => $this->rating,
            'notes'      => $this->notes,
            'added_at'   => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'movie'      => new MovieResource($this->whenLoaded('movie')),
        ];
    }
}
