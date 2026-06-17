<?php

declare(strict_types=1);

namespace App\Filters;

class WatchlistFilter extends QueryFilter
{
    protected function filters(): array
    {
        return $this->request->only(['status', 'search']);
    }

    protected function status(string $value): void
    {
        $this->builder->where('status', $value);
    }

    protected function search(string $value): void
    {
        $this->builder->whereHas('movie', fn ($q) => $q->where('title', 'like', '%'.$value.'%'));
    }
}
