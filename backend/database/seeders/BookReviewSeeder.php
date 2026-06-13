<?php

namespace Database\Seeders;

use App\Enums\BookReviewStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\BookReview;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Alimente des témoignages lecteurs approuvés pour la démo.
 */
class BookReviewSeeder extends Seeder
{
  /**
   * Exécute le seed des avis lecteurs.
   */
  public function run(): void
  {
    $book = Book::query()->where('slug', 'eglise-face-aux-defis-de-lheure')->first();

    if ($book === null) {
      return;
    }

    $reviewers = [
      [
        'email' => 'esther.demo@kenluamba.com',
        'full_name' => 'Esther M.',
        'role' => 'Responsable de cellule',
        'rating' => 5,
        'content' => 'Un livre qui secoue avec douceur et reconstruit avec profondeur. Je l\'ai lu deux fois.',
      ],
      [
        'email' => 'daniel.demo@kenluamba.com',
        'full_name' => 'Daniel K.',
        'role' => 'Étudiant en théologie',
        'rating' => 5,
        'content' => 'La clarté de l\'analyse m\'a marqué. Chaque chapitre se médite autant qu\'il se lit.',
      ],
      [
        'email' => 'grace.demo@kenluamba.com',
        'full_name' => 'Grâce N.',
        'role' => 'Lectrice',
        'rating' => 5,
        'content' => 'Enfin un ouvrage qui parle vrai à notre génération sans jamais la flatter.',
      ],
    ];

    foreach ($reviewers as $reviewer) {
      $user = User::query()->updateOrCreate(
        ['email' => $reviewer['email']],
        [
          'name' => $reviewer['full_name'],
          'full_name' => $reviewer['full_name'],
          'password' => Hash::make('KenLuamba@2026'),
          'role' => UserRole::Client,
          'is_active' => true,
          'email_verified_at' => now(),
        ],
      );

      BookReview::query()->updateOrCreate(
        [
          'book_id' => $book->id,
          'user_id' => $user->id,
        ],
        [
          'author_role' => $reviewer['role'],
          'rating' => $reviewer['rating'],
          'content' => $reviewer['content'],
          'status' => BookReviewStatus::Approved,
          'moderated_at' => now(),
        ],
      );
    }
  }
}
