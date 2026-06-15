<?php

namespace App\Support;

use Database\Seeders\AdminAppearancePermissionSeeder;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\AuthorSeeder;
use Database\Seeders\BookReleaseSubscriptionPermissionSeeder;
use Database\Seeders\BookReviewSeeder;
use Database\Seeders\CatalogBookSeeder;
use Database\Seeders\ContactSettingPermissionSeeder;
use Database\Seeders\CourierUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PickupPointSeeder;
use Database\Seeders\ShippingPermissionSeeder;
use Database\Seeders\ShippingSettingSeeder;
use Database\Seeders\ShopSettingPermissionSeeder;

/**
 * Catalogue des seeders exécutables depuis l'admin de déploiement.
 */
class DeploySeederRegistry
{
  /**
   * Retourne la liste des seeders disponibles avec métadonnées.
   *
   * @return list<array{class: class-string, key: string, label: string, group: string, description: string}>
   */
  public static function all(): array
  {
    return [
      [
        'class' => AuthorSeeder::class,
        'key' => 'AuthorSeeder',
        'label' => 'Auteur (Ken Luamba)',
        'group' => 'Contenu',
        'description' => 'Profil auteur, biographie et photos d\'affichage.',
      ],
      [
        'class' => CatalogBookSeeder::class,
        'key' => 'CatalogBookSeeder',
        'label' => 'Catalogue livres',
        'group' => 'Contenu',
        'description' => '4 ouvrages, couvertures, extraits feuilletables et formats.',
      ],
      [
        'class' => BookReviewSeeder::class,
        'key' => 'BookReviewSeeder',
        'label' => 'Avis lecteurs',
        'group' => 'Contenu',
        'description' => 'Avis de démonstration sur les livres publiés.',
      ],
      [
        'class' => AdminUserSeeder::class,
        'key' => 'AdminUserSeeder',
        'label' => 'Compte administrateur',
        'group' => 'Utilisateurs',
        'description' => 'Utilisateur admin@kenluamba.com.',
      ],
      [
        'class' => CourierUserSeeder::class,
        'key' => 'CourierUserSeeder',
        'label' => 'Compte livreur',
        'group' => 'Utilisateurs',
        'description' => 'Utilisateur livreur de démonstration.',
      ],
      [
        'class' => PickupPointSeeder::class,
        'key' => 'PickupPointSeeder',
        'label' => 'Points de retrait',
        'group' => 'Boutique',
        'description' => 'Points de collecte pour les commandes.',
      ],
      [
        'class' => ShippingSettingSeeder::class,
        'key' => 'ShippingSettingSeeder',
        'label' => 'Paramètres livraison',
        'group' => 'Boutique',
        'description' => 'Zones, villes et tarifs de livraison.',
      ],
      [
        'class' => AdminAppearancePermissionSeeder::class,
        'key' => 'AdminAppearancePermissionSeeder',
        'label' => 'Permissions apparence admin',
        'group' => 'Permissions',
        'description' => 'Droits Filament pour les réglages visuels.',
      ],
      [
        'class' => ContactSettingPermissionSeeder::class,
        'key' => 'ContactSettingPermissionSeeder',
        'label' => 'Permissions contact',
        'group' => 'Permissions',
        'description' => 'Droits Filament pour la page contact.',
      ],
      [
        'class' => ShippingPermissionSeeder::class,
        'key' => 'ShippingPermissionSeeder',
        'label' => 'Permissions livraison',
        'group' => 'Permissions',
        'description' => 'Droits Filament pour la gestion livraison.',
      ],
      [
        'class' => ShopSettingPermissionSeeder::class,
        'key' => 'ShopSettingPermissionSeeder',
        'label' => 'Permissions boutique',
        'group' => 'Permissions',
        'description' => 'Droits Filament pour les réglages boutique.',
      ],
      [
        'class' => BookReleaseSubscriptionPermissionSeeder::class,
        'key' => 'BookReleaseSubscriptionPermissionSeeder',
        'label' => 'Permissions alertes sortie',
        'group' => 'Permissions',
        'description' => 'Droits Filament pour les abonnements de sortie.',
      ],
      [
        'class' => DatabaseSeeder::class,
        'key' => 'DatabaseSeeder',
        'label' => 'Tous (DatabaseSeeder)',
        'group' => 'Ensemble',
        'description' => 'Exécute la chaîne complète définie dans DatabaseSeeder.',
      ],
    ];
  }

  /**
   * Options pour les champs de formulaire Filament (clé => libellé).
   *
   * @return array<string, string>
   */
  public static function options(): array
  {
    $options = [];

    foreach (self::all() as $entry) {
      $options[$entry['key']] = '['.$entry['group'].'] '.$entry['label'];
    }

    return $options;
  }

  /**
   * Résout une clé de seeder vers sa classe PHP.
   *
   * @param string $key Clé courte (ex. CatalogBookSeeder)
   * @return class-string|null Classe du seeder ou null si introuvable
   */
  public static function resolveClass(string $key): ?string
  {
    foreach (self::all() as $entry) {
      if ($entry['key'] === $key) {
        return $entry['class'];
      }
    }

    return null;
  }

  /**
   * Valide et retourne les classes à exécuter.
   *
   * @param list<string> $keys Clés de seeders sélectionnés
   * @return list<class-string> Classes résolues
   */
  public static function resolveClasses(array $keys): array
  {
    $classes = [];

    foreach ($keys as $key) {
      $class = self::resolveClass($key);

      if ($class !== null) {
        $classes[] = $class;
      }
    }

    return array_values(array_unique($classes));
  }
}
