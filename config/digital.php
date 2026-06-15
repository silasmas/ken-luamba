<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Restrictions d'accès aux contenus numériques
  |--------------------------------------------------------------------------
  */

  'stream_expiry_minutes' => (int) env('DIGITAL_STREAM_EXPIRY_MINUTES', 120),

  'max_downloads' => (int) env('DIGITAL_MAX_DOWNLOADS', 5),

  'share_expiry_minutes' => (int) env('DIGITAL_SHARE_EXPIRY_MINUTES', 2880),

  'share_reading_minutes' => (int) env('DIGITAL_SHARE_READING_MINUTES', 90),

  'share_max_links' => (int) env('DIGITAL_SHARE_MAX_LINKS', 3),

];
