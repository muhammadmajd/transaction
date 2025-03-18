<?php

namespace App\Repositories;

use App\Models\FirebaseToken;
use App\Models\User;

class FireBaseTokenRepository
{
    /**
     * Get the Firebase token by user phone number.
     *
     * @param string $phone
     * @return FirebaseToken|null
     */
    public function getFirebaseTokenByPhone(string $phone)
    {
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return null;
        }

        return FirebaseToken::where('user_id', $user->id)
                            ->where('active', true)
                            ->pluck('token')
                            ->first();
    }
}