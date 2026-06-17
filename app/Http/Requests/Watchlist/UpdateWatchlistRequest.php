<?php

declare(strict_types=1);

namespace App\Http\Requests\Watchlist;

use App\Enums\Watchlist\WatchlistStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWatchlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(WatchlistStatus::class)],
            'rating' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10'],
            'notes'  => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
