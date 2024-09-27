<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'part' => 'required|string|max:255',
            'min_score' => 'required|integer|min:0',
            'max_score' => 'required|integer|min:0',
            'avg_score' => 'required|integer|min:0',
        ];
    }
}
