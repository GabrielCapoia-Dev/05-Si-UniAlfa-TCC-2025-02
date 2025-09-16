<?php

namespace App\Policies;

use App\Models\Turma;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TurmaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('Listar Turmas');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Turma $turma): bool
    {
        return $user->hasPermissionTo('Listar Turmas');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('Criar Turmas');

    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Turma $turma): bool
    {
        return $user->hasPermissionTo('Editar Turmas');

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Turma $turma): bool
    {
        return $user->hasPermissionTo('Excluir Turmas');

    }

    // /**
    //  * Determine whether the user can restore the model.
    //  */
    // public function restore(User $user, Turma $turma): bool
    // {
    //     return false;
    // }

    // /**
    //  * Determine whether the user can permanently delete the model.
    //  */
    // public function forceDelete(User $user, Turma $turma): bool
    // {
    //     return false;
    // }
}
