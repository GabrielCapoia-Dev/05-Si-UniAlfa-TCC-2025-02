<?php

namespace App\Policies;

use App\Models\Escola;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EscolaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('Listar Escolas');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Escola $escola): bool
    {
        return $user->hasPermissionTo('Listar Escolas');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('Criar Escolas');

    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Escola $escola): bool
    {
        return $user->hasPermissionTo('Editar Escolas');

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Escola $escola): bool
    {
        return $user->hasPermissionTo('Excluir Escolas');

    }

    // /**
    //  * Determine whether the user can restore the model.
    //  */
    // public function restore(User $user, Escola $escola): bool
    // {
    //     return false;
    // }

    // /**
    //  * Determine whether the user can permanently delete the model.
    //  */
    // public function forceDelete(User $user, Escola $escola): bool
    // {
    //     return false;
    // }
}
