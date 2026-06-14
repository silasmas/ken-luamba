<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Invitation;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvitationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Invitation');
    }

    public function view(AuthUser $authUser, Invitation $invitation): bool
    {
        return $authUser->can('View:Invitation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Invitation');
    }

    public function update(AuthUser $authUser, Invitation $invitation): bool
    {
        return $authUser->can('Update:Invitation');
    }

    public function delete(AuthUser $authUser, Invitation $invitation): bool
    {
        return $authUser->can('Delete:Invitation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Invitation');
    }

    public function restore(AuthUser $authUser, Invitation $invitation): bool
    {
        return $authUser->can('Restore:Invitation');
    }

    public function forceDelete(AuthUser $authUser, Invitation $invitation): bool
    {
        return $authUser->can('ForceDelete:Invitation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Invitation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Invitation');
    }

    public function replicate(AuthUser $authUser, Invitation $invitation): bool
    {
        return $authUser->can('Replicate:Invitation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Invitation');
    }

}