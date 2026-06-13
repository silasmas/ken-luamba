<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BookReview;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookReviewPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BookReview');
    }

    public function view(AuthUser $authUser, BookReview $bookReview): bool
    {
        return $authUser->can('View:BookReview');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BookReview');
    }

    public function update(AuthUser $authUser, BookReview $bookReview): bool
    {
        return $authUser->can('Update:BookReview');
    }

    public function delete(AuthUser $authUser, BookReview $bookReview): bool
    {
        return $authUser->can('Delete:BookReview');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BookReview');
    }

    public function restore(AuthUser $authUser, BookReview $bookReview): bool
    {
        return $authUser->can('Restore:BookReview');
    }

    public function forceDelete(AuthUser $authUser, BookReview $bookReview): bool
    {
        return $authUser->can('ForceDelete:BookReview');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BookReview');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BookReview');
    }

    public function replicate(AuthUser $authUser, BookReview $bookReview): bool
    {
        return $authUser->can('Replicate:BookReview');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BookReview');
    }

}