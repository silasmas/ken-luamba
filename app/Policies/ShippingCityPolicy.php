<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ShippingCity;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShippingCityPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ShippingCity');
    }

    public function view(AuthUser $authUser, ShippingCity $shippingCity): bool
    {
        return $authUser->can('View:ShippingCity');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ShippingCity');
    }

    public function update(AuthUser $authUser, ShippingCity $shippingCity): bool
    {
        return $authUser->can('Update:ShippingCity');
    }

    public function delete(AuthUser $authUser, ShippingCity $shippingCity): bool
    {
        return $authUser->can('Delete:ShippingCity');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ShippingCity');
    }

    public function restore(AuthUser $authUser, ShippingCity $shippingCity): bool
    {
        return $authUser->can('Restore:ShippingCity');
    }

    public function forceDelete(AuthUser $authUser, ShippingCity $shippingCity): bool
    {
        return $authUser->can('ForceDelete:ShippingCity');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ShippingCity');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ShippingCity');
    }

    public function replicate(AuthUser $authUser, ShippingCity $shippingCity): bool
    {
        return $authUser->can('Replicate:ShippingCity');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ShippingCity');
    }

}