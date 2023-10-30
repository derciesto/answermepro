<?php
return [
    /**
     * All requests require your user credentials & API key, which you can find under "Account Settings"
     * in [https://www.experttexting.com/appv2/Dashboard/Profile] .
     */
    'username' => env('SMS_USERNAME', 'raeparth1'), // Required. Your ET username. Ex: starcity

    'password' => env('SMS_PASSWORD', 'onfire3434'), // Required. Your ET password. Ex: StarCity123

    'api_key' => env('SMS_API_KEY', '4uz0aiv6p48g035'),  // Required. Your API key. Ex: sswmp8r7l63y

    'from' => env('SMS_FROM_NUMBER', '18339621836'),
];
