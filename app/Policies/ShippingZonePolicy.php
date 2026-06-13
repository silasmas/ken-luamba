<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ShippingZone;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShippingZonePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ShippingZone');
    }

    public function view(AuthUser $authUser, ShippingZone $shippingZone): bool
    {
        return $authUser->can('View:ShippingZone');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ShippingZone');
    }

    public function update(AuthUser $authUser, ShippingZone $shippingZone): bool
    {
        return $authUser->can('Update:ShippingZone');
    }

    public function delete(AuthUser $authUser, ShippingZone $shippingZone): bool
    {
        return $authUser->can('Delete:ShippingZone');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ShippingZone');
    }

    public function restore(AuthUser $authUser, ShippingZone $shippingZone): bool
    {
        return $authUser->can('Restore:ShippingZone');
    }

    public function forceDelete(AuthUser $authUser, ShippingZone $shippingZone): bool
    {
        return $authUser->can('ForceDelete:ShippingZone');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ShippingZone');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ShippingZone');
    }

    public function replicate(AuthUser $authUser, ShippingZone $shippingZone): bool
    {
        return $authUser->can('Replicate:ShippingZone');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ShippingZone');
    }

}