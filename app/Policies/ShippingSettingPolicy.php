<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ShippingSetting;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShippingSettingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ShippingSetting');
    }

    public function view(AuthUser $authUser, ShippingSetting $shippingSetting): bool
    {
        return $authUser->can('View:ShippingSetting');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ShippingSetting');
    }

    public function update(AuthUser $authUser, ShippingSetting $shippingSetting): bool
    {
        return $authUser->can('Update:ShippingSetting');
    }

    public function delete(AuthUser $authUser, ShippingSetting $shippingSetting): bool
    {
        return $authUser->can('Delete:ShippingSetting');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ShippingSetting');
    }

    public function restore(AuthUser $authUser, ShippingSetting $shippingSetting): bool
    {
        return $authUser->can('Restore:ShippingSetting');
    }

    public function forceDelete(AuthUser $authUser, ShippingSetting $shippingSetting): bool
    {
        return $authUser->can('ForceDelete:ShippingSetting');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ShippingSetting');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ShippingSetting');
    }

    public function replicate(AuthUser $authUser, ShippingSetting $shippingSetting): bool
    {
        return $authUser->can('Replicate:ShippingSetting');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ShippingSetting');
    }

}