<?php

return [
  'flexpay_mobile_money_api_type' => '1',
  'flexpay_card_api_type' => '2',

  'flexpay_mobile_providers' => (function (): array {
    $raw = env('FLEXPAY_MOBILE_PROVIDERS');
    if ($raw) {
      $decoded = json_decode((string) $raw, true);
      if (is_array($decoded) && $decoded !== []) {
        return $decoded;
      }
    }

    return [
      ['type' => 'mpesa', 'code' => 'mpesa', 'label' => 'M-Pesa', 'msisdn_regex' => '^2438[123][0-9]{7}$', 'phone_hint' => '24381/82/83XXXXXXX'],
      ['type' => 'airtel', 'code' => 'airtel', 'label' => 'Airtel Money', 'msisdn_regex' => '^24399[0-9]{7}$', 'phone_hint' => '24399XXXXXXX'],
      ['type' => 'orange', 'code' => 'orange', 'label' => 'Orange Money', 'msisdn_regex' => '^2438[459][0-9]{7}$', 'phone_hint' => '24384/85/89XXXXXXX'],
      ['type' => 'afri', 'code' => 'afri', 'label' => 'Afri Money', 'msisdn_regex' => '^24390[0-9]{7}$', 'phone_hint' => '24390XXXXXXX'],
    ];
  })(),
];
