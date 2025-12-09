<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\UserRole;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'is_active',
        'must_change_password',
        'last_login_at',
        'last_login_ip',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // Relations
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function etudiant()
    {
        return $this->hasOne(Etudiant::class);
    }

    public function professeur()
    {
        return $this->hasOne(Professeur::class);
    }

    public function annoncesCrees()
    {
        return $this->hasMany(Annonce::class, 'auteur_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function messagesEnvoyes()
    {
        return $this->hasMany(Message::class, 'expediteur_id');
    }

    public function messagesRecus()
    {
        return $this->hasMany(Message::class, 'destinataire_id');
    }

    public function logsActivite()
    {
        return $this->hasMany(LogActivite::class);
    }

    // Accesseurs
    public function isAdmin(): bool
    {
        return $this->role->name === UserRole::ADMIN->value;
    }

    public function isProfesseur(): bool
    {
        return $this->role->name === UserRole::PROFESSEUR->value;
    }

    public function isEtudiant(): bool
    {
        return $this->role->name === UserRole::ETUDIANT->value;
    }

    public function getRoleNameAttribute(): string
    {
        return $this->role->name;
    }
}
