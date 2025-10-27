<?php

namespace App\Traits;

use App\Models\UserToken;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

trait UserTokenTraits
{
    /**
     * Create web token
     */
    public function createWebToken()
    {
        $token = Str::random(60);
        
        // Store token in user_tokens table
        $this->userToken()->updateOrCreate(
            ['user_id' => $this->id],
            ['web_access_token' => $token]
        );
        
        return $token;
    }

    /**
     * Create app token
     */
    public function createAppToken($deviceToken = null, $deviceType = null)
    {
        $token = Str::random(60);
        
        // Store token in user_tokens table
        $this->userToken()->updateOrCreate(
            ['user_id' => $this->id],
            [
                'app_access_token' => $token,
                'device_token' => $deviceToken,
                'device_type' => $deviceType
            ]
        );
        
        return $token;
    }

    /**
     * Create admin token
     */
    public function createAdminToken()
    {
        $tokenData = [
            'user_id' => $this->id,
            'timestamp' => time(),
            'type' => 'admin'
        ];
        
        $encryptedToken = Crypt::encryptString(json_encode($tokenData));
        
        // Store token in user_tokens table
        $this->userToken()->updateOrCreate(
            ['user_id' => $this->id],
            ['web_access_token' => $encryptedToken]
        );
        
        return $encryptedToken;
    }
}
