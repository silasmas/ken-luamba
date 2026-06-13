<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\QuantityDiscount;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuantityDiscountPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:QuantityDiscount');
    }

    public function view(AuthUser $authUser, QuantityDiscount $quantityDiscount): bool
    {
        return $authUser->can('View:QuantityDiscount');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:QuantityDiscount');
    }

    public function update(AuthUser $authUser, QuantityDiscount $quantityDiscount): bool
    {
        return $authUser->can('Update:QuantityDiscount');
    }

    public function delete(AuthUser $authUser, QuantityDiscount $quantityDiscount): bool
    {
        return $authUser->can('Delete:QuantityDiscount');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:QuantityDiscount');
    }

    public function restore(AuthUser $authUser, QuantityDiscount $quantityDiscount): bool
    {
        return $authUser->can('Restore:QuantityDiscount');
    }

    public function forceDelete(AuthUser $authUser, QuantityDiscount $quantityDiscount): bool
    {
        return $authUser->can('ForceDelete:QuantityDiscount');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:QuantityDiscount');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:QuantityDiscount');
    }

    public function replicate(AuthUser $authUser, QuantityDiscount $quantityDiscount): bool
    {
        return $authUser->can('Replicate:QuantityDiscount');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:QuantityDiscount');
    }

}