<?php

declare(strict_types=1);

namespace App\Http\Requests\Watchlist;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWatchlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'imdb_id' => ['nullable', 'string', 'regex:/^tt\d+$/', 'required_without:title'],
            'title'   => ['nullable', 'string', 'max:255', 'required_without:imdb_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'imdb_id.required_without' => 'Provide either imdb_id or title.',
            'title.required_without'   => 'Provide either imdb_id or title.',
        ];
    }
}
