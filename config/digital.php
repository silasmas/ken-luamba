<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Restrictions d'accès aux contenus numériques
  |--------------------------------------------------------------------------
  */

  'stream_expiry_hours' => (int) env('DIGITAL_STREAM_EXPIRY_HOURS', 2),

  'max_downloads' => (int) env('DIGITAL_MAX_DOWNLOADS', 5),

  'share_expiry_hours' => (int) env('DIGITAL_SHARE_EXPIRY_HOURS', 48),

  'share_max_links' => (int) env('DIGITAL_SHARE_MAX_LINKS', 3),

];
