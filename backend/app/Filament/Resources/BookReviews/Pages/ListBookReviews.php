<?php

namespace App\Filament\Resources\BookReviews\Pages;

use App\Filament\Resources\BookReviews\BookReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListBookReviews extends ListRecords
{
  protected static string $resource = BookReviewResource::class;
}
