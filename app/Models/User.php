<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch',
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

    /**
     * Determine if the user has the admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Determine if the user has the kasir role.
     */
    public function isKasir(): bool
    {
        return $this->role === 'kasir';
    }

    /**
     * Determine if the user has the owner role.
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Get allowed enum values for the branch column in the users table.
     */
    public static function getBranchEnumValues(): array
    {
        try {
            $type = \DB::select("SHOW COLUMNS FROM users WHERE Field = 'branch'")[0]->Type;
            if (preg_match('/^enum\((.*)\)$/', $type, $matches)) {
                $values = [];
                foreach (explode(',', $matches[1]) as $value) {
                    $values[] = trim($value, "'");
                }
                return $values;
            }
        } catch (\Throwable $e) {
            // Fallback if schema query fails
        }
        return ['Pusat Cianjur', 'Cabang Cianjur', 'Cabang Ciranjang', 'Cabang Rumah', 'Cabang Online'];
    }
}
