<?php

return [
  'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 10),
  'max_requests_per_hour' => (int) env('OTP_MAX_REQUESTS_PER_HOUR', 20),
  'code_length' => 6,
  'disable_rate_limit' => (bool) env('OTP_DISABLE_RATE_LIMIT', false),
];
