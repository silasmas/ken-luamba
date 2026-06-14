<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Pilote SMS
  |--------------------------------------------------------------------------
  |
  | keccel : envoi via l'API Kecel (recommandé en production)
  | manual : ouverture de l'application SMS locale (développement)
  |
  */

  'driver' => env('SMS_DRIVER', 'manual'),

  'token' => env('SMS_TOKEN'),

  'from' => env('SMS_FROM', 'KenLuamba'),

  'url' => env('SMS_URL', 'https://api.keccel.com/sms/v1/message.asp'),

  'balance_url' => env('BALANCE_URL', 'https://api.keccel.com/sms/balance.asp'),

];
