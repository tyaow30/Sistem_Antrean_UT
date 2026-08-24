<?php

namespace App\Models;

use App\Models\Gerai;
use App\Models\Loket;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'active_session_id',
        'role',
        'assigned_gerai_id',
        'assigned_loket_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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

    /**
     * Relasi ke Loket yang ditugaskan
     */
    public function assignedLoket()
    {
        return $this->belongsTo(Loket::class, 'assigned_loket_id');
    }

    /**
     * Relasi ke Gerai yang ditugaskan
     */
    public function assignedGerai()
    {
        return $this->belongsTo(Gerai::class, 'assigned_gerai_id');
    }

    // Alias untuk kompatibilitas kode lama
    public function loket()
    {
        return $this->assignedLoket();
    }

    public function gerai()
    {
        return $this->assignedGerai();
    }
}