<?php

declare(strict_types=1);

namespace App\Http\Requests\Watchlist;

use App\Enums\Watchlist\WatchlistStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WatchlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'   => ['sometimes', Rule::enum(WatchlistStatus::class)],
            'search'   => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
