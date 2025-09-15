<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // use HasFactory, Notifiable;
    use HasApiTokens, HasFactory, Notifiable, HasRoles; 

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    protected $guard_name = 'api';


    // Obras a las que pertenece (pivot obra_user)
    public function obras()
    {
        return $this->belongsToMany(Obra::class, 'obra_user')->withTimestamps();
    }

    // ¿Pertenece a esta obra?
    public function belongsToObra(int $obraId): bool
    {
        return $this->obras()->where('obras.id', $obraId)->exists();
    }

    // Sincroniza roles en una obra concreta (v6 teams)
    public function syncRolesInObra(int $obraId, array $roles): void
    {
        setPermissionsTeamId($obraId);
        $this->unsetRelation('roles')->unsetRelation('permissions');
        $this->syncRoles($roles);
    }

    // Obtiene nombres de roles en una obra concreta
    public function roleNamesInObra(int $obraId)
    {
        setPermissionsTeamId($obraId);
        $this->unsetRelation('roles')->unsetRelation('permissions');
        return $this->roles->pluck('name');
    }
}
