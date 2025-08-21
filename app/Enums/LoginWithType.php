<?php

namespace App\Enums;

enum LoginWithType: string
{
    case PASSWORD = 'password';
    case OTP = 'otp';
}