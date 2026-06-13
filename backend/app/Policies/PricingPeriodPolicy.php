<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PricingPeriod;
use Illuminate\Auth\Access\HandlesAuthorization;

class PricingPeriodPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PricingPeriod');
    }

    public function view(AuthUser $authUser, PricingPeriod $pricingPeriod): bool
    {
        return $authUser->can('View:PricingPeriod');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PricingPeriod');
    }

    public function update(AuthUser $authUser, PricingPeriod $pricingPeriod): bool
    {
        return $authUser->can('Update:PricingPeriod');
    }

    public function delete(AuthUser $authUser, PricingPeriod $pricingPeriod): bool
    {
        return $authUser->can('Delete:PricingPeriod');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PricingPeriod');
    }

    public function restore(AuthUser $authUser, PricingPeriod $pricingPeriod): bool
    {
        return $authUser->can('Restore:PricingPeriod');
    }

    public function forceDelete(AuthUser $authUser, PricingPeriod $pricingPeriod): bool
    {
        return $authUser->can('ForceDelete:PricingPeriod');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PricingPeriod');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PricingPeriod');
    }

    public function replicate(AuthUser $authUser, PricingPeriod $pricingPeriod): bool
    {
        return $authUser->can('Replicate:PricingPeriod');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PricingPeriod');
    }

}