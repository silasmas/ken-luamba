<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Author;
use App\Models\Book;
use App\Support\MediaUrl;
use Illuminate\Support\Facades\Storage;

$author = Author::query()->first();
$book = Book::query()->where('slug', 'eglise-face-aux-defis-de-lheure')->first();

echo 'APP_URL='.config('app.url').PHP_EOL;
echo 'PUBLIC_DISK_URL='.config('filesystems.disks.public.url').PHP_EOL;
echo 'storage_link_exists='.(is_link(public_path('storage')) || is_dir(public_path('storage')) ? 'yes' : 'no').PHP_EOL;

if ($author) {
  echo 'author_path='.$author->profile_image.PHP_EOL;
  echo 'author_url='.MediaUrl::fromPath($author->profile_image).PHP_EOL;
  echo 'author_disk_exists='.(Storage::disk('public')->exists($author->profile_image) ? 'yes' : 'no').PHP_EOL;
  echo 'author_public_exists='.(file_exists(public_path('storage/'.$author->profile_image)) ? 'yes' : 'no').PHP_EOL;
}

echo 'filament_disk='.var_export(config('filament.default_filesystem_disk'), true).PHP_EOL;
echo 'filesystem_default='.config('filesystems.default').PHP_EOL;

if ($book) {
  echo 'book_path='.$book->cover_image.PHP_EOL;
  echo 'book_url='.MediaUrl::fromPath($book->cover_image).PHP_EOL;
  echo 'book_disk_exists='.(Storage::disk('public')->exists($book->cover_image) ? 'yes' : 'no').PHP_EOL;
  echo 'book_public_exists='.(file_exists(public_path('storage/'.$book->cover_image)) ? 'yes' : 'no').PHP_EOL;
}
