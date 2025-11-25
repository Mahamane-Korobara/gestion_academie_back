<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateFiliereRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:filieres,code'],
            'duree_annees' => ['required', 'integer', 'min:1', 'max:10'],
            'description' => ['nullable', 'string'],
        ];
    }
}