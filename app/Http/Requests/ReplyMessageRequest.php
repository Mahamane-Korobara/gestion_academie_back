<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplyMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sujet' => 'nullable|string|max:255',
            'contenu' => 'required|string',
        ];
    }

    // Empêcher un utilisateur d'envoyer un message à lui-même
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->destinataire_id == $this->user()->id) {
                $validator->errors()->add('destinataire_id', 'Vous ne pouvez pas vous envoyer un message à vous-même.');
            }
        });
    }
}