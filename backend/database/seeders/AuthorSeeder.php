<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
  /**
   * Crée le profil auteur principal Ken Luamba.
   */
  public function run(): void
  {
    Author::query()->updateOrCreate(
      ['slug' => 'ken-luamba'],
      [
        'full_name' => 'Pasteur Ken Luamba',
        'title' => 'Pasteur, auteur et conférencier',
        'short_bio' => 'Pasteur Ken Luamba partage à travers ses ouvrages une vision de foi, d\'espérance et de transformation.',
        'full_bio' => 'Le pasteur Ken Luamba est une personnalité reconnue pour son ministère et ses enseignements. À travers ses livres, il accompagne des milliers de lecteurs dans leur cheminement spirituel et personnel.',
        'featured_quote' => 'La foi transforme ce que nous croyons possible.',
        'social_links' => [
          'facebook' => 'https://facebook.com/',
          'youtube' => 'https://youtube.com/',
        ],
        'is_primary' => true,
        'is_published' => true,
        'meta_title' => 'Pasteur Ken Luamba — Auteur',
        'meta_description' => 'Découvrez la biographie et les ouvrages du pasteur Ken Luamba.',
      ]
    );
  }
}
