<?php

namespace App\Policies;

use App\Models\Rota;
use App\Models\User;
use Illuminate\Auth\Access\Response;


class RotaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('Listar Rotas');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Rota $rota): bool
    {
        return $user->hasPermissionTo('Listar Rotas');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('Criar Rotas');

    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Rota $rota): bool
    {
        return $user->hasPermissionTo('Editar Rotas');

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Rota $rota): bool
    {
        return $user->hasPermissionTo('Excluir Rotas');

    }

    // /**
    //  * Determine whether the user can restore the model.
    //  */
    // public function restore(User $user, Rota $rota): bool
    // {
    //     return false;
    // }

    // /**
    //  * Determine whether the user can permanently delete the model.
    //  */
    // public function forceDelete(User $user, Rota $rota): bool
    // {
    //     return false;
    // }
}