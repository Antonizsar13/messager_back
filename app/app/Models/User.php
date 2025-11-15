<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\TypeFile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
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
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function findForPassport($username)
    {
        return $this->where('phone', $username)->first();
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function avatars()
    {
        return $this->files()->where('type', TypeFile::avatar)->orderBy('created_at', 'desc');
    }

    public function getAvatarsArrayAttribute()
    {
        return $this->avatars->map(function ($file) {
            return [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'url' => $file->url,
                'created_at' => $file->created_at,
            ];
        });
    }
}
