<?php

namespace App\Support;

/**
 * Construit les liens téléphone, WhatsApp et e-mail à partir des valeurs saisies.
 */
class ContactLinkHelper
{
  /**
   * Extrait les chiffres d'un numéro pour les liens tel: et wa.me.
   *
   * @param string|null $phone Numéro affiché
   * @return string Chiffres uniquement
   */
  public static function digits(?string $phone): string
  {
    if ($phone === null || $phone === '') {
      return '';
    }

    return preg_replace('/\D+/', '', $phone) ?? '';
  }

  /**
   * Produit un lien d'appel téléphonique.
   *
   * @param string|null $phone Numéro affiché
   * @return string|null URL tel: ou null
   */
  public static function telHref(?string $phone): ?string
  {
    $digits = self::digits($phone);

    if ($digits === '') {
      return null;
    }

    return 'tel:+'.$digits;
  }

  /**
   * Produit un lien WhatsApp.
   *
   * @param string|null $phone Numéro affiché
   * @return string|null URL wa.me ou null
   */
  public static function whatsappHref(?string $phone): ?string
  {
    $digits = self::digits($phone);

    if ($digits === '') {
      return null;
    }

    return 'https://wa.me/'.$digits;
  }

  /**
   * Produit un lien mailto.
   *
   * @param string|null $email Adresse e-mail
   * @return string|null URL mailto: ou null
   */
  public static function mailtoHref(?string $email): ?string
  {
    $email = trim((string) $email);

    if ($email === '') {
      return null;
    }

    return 'mailto:'.$email;
  }
}
