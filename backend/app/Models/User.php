<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const PERFIL_ADMIN = 'admin';
    public const PERFIL_TECNICO = 'tecnico';
    public const PERFIL_CONSULTA = 'consulta';

    public const PERFIS = [
        self::PERFIL_ADMIN,
        self::PERFIL_TECNICO,
        self::PERFIL_CONSULTA,
    ];

    protected $fillable = [
        'nome',
        'email',
        'password',
        'tipo',
        'ativo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
        ];
    }

    public function temPerfil(string ...$perfis): bool
    {
        return in_array($this->tipo, $perfis, true);
    }

    public function isAdmin(): bool
    {
        return $this->tipo === self::PERFIL_ADMIN;
    }
}
