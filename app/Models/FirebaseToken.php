<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FirebaseToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the user that owns the Firebase token.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convert model instance to JSON.
     */
    public function userToJson($options = 0)
    {
        return json_encode([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'token' => $this->token,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ], $options);
    }

    /**
     * Create a FirebaseToken instance from JSON.
     */
    public static function fBFromJson($json)
    {
        $data = json_decode($json, true);
        return new self($data);
    }
}
