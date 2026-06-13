<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PickupPoint;
use Illuminate\Auth\Access\HandlesAuthorization;

class PickupPointPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PickupPoint');
    }

    public function view(AuthUser $authUser, PickupPoint $pickupPoint): bool
    {
        return $authUser->can('View:PickupPoint');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PickupPoint');
    }

    public function update(AuthUser $authUser, PickupPoint $pickupPoint): bool
    {
        return $authUser->can('Update:PickupPoint');
    }

    public function delete(AuthUser $authUser, PickupPoint $pickupPoint): bool
    {
        return $authUser->can('Delete:PickupPoint');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PickupPoint');
    }

    public function restore(AuthUser $authUser, PickupPoint $pickupPoint): bool
    {
        return $authUser->can('Restore:PickupPoint');
    }

    public function forceDelete(AuthUser $authUser, PickupPoint $pickupPoint): bool
    {
        return $authUser->can('ForceDelete:PickupPoint');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PickupPoint');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PickupPoint');
    }

    public function replicate(AuthUser $authUser, PickupPoint $pickupPoint): bool
    {
        return $authUser->can('Replicate:PickupPoint');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PickupPoint');
    }

}