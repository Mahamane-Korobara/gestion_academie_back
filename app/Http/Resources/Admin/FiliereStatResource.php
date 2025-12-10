<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class FiliereStatResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'   => $this->id,
            'name' => $this->nom,
            'code' => $this->code,
            'count' => $this->etudiants_count
        ];
    }
}