<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Longueur du token public d'invitation
  |--------------------------------------------------------------------------
  |
  | Un token plus court réduit la taille des SMS (lien {invitation_link}).
  | 10 caractères alphanumériques ≈ 839 billions de combinaisons.
  |
  */
  'token_length' => (int) env('INVITATION_TOKEN_LENGTH', 10),

  /*
  |--------------------------------------------------------------------------
  | Chemin public court pour les liens d'invitation (economie SMS)
  |--------------------------------------------------------------------------
  */
  'public_path' => env('INVITATION_PUBLIC_PATH', 'i'),
];
