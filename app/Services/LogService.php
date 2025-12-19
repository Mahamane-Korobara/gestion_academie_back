<?php

namespace App\Services;

use App\Models\LogActivite;
use App\Enums\ActionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LogService
{
    /**
     * Enregistrer une activité
     */
    public static function write(
        ActionLog $action,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        /** @var User|null $user */
        $user = Auth::user(); 
        $request = request();

        $rolePrefix = "";
        if ($user) {
            if ($user->isAdmin()) $rolePrefix = "[ADMIN] ";
            if ($user->isProfesseur()) $rolePrefix = "[PROF] ";
        }

        LogActivite::create([
            'user_id'     => Auth::id(), // Plus d'erreur Intelephense ici
            'action'      => $action->value,
            'model_type'  => $model ? get_class($model) : null,
            'model_id'    => $model ? $model->getKey() : null,
            'description' => $rolePrefix . $description,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);
    }
}