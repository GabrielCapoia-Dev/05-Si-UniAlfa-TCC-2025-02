<?php

namespace App\Services;

use App\Models\User;
use App\Models\IgnoredUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class UserService
{
    /** Retorna se o usuário é Admin pelo Gate. */
    public function ehAdmin(?User $user = null): bool
    {
        return Gate::allows('admin-only', $user);
    }

    /** Aplica filtro de listagem: não-admin não vê usuários com role Admin. */
    public function listarUsuariosQuery(Builder $base, ?User $user): Builder
    {
        if (! $this->ehAdmin($user)) {
            $base->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Admin'));
        }
        return $base;
    }

    /** Retorna as opções de roles visíveis ao usuário (oculta 'Admin' para não-admin). */
    public function opcoesDeRoles(Builder $base, ?User $user): Builder
    {
        return $this->ehAdmin($user) ? $base : $base->where('name', '!=', 'Admin');
    }

    /** Indica se o campo de role deve ficar desabilitado. */
    public function desabilitarCampoRole(?User $user, ?User $record, string $context): bool
    {
        if ($context === 'create' || ! $record) return false;
        if ($record->hasRole('Admin')) return true;
        if ($user && $record->id === $user->id) return true;
        return false;
    }

    /** Indica se o toggle de aprovação de e-mail deve ser visível. */
    public function podeVerToggleAprovacaoEmail(?User $user, ?User $record, string $context): bool
    {
        if ($context === 'create') return true;
        if (! $user || ! $this->ehAdmin($user)) return false;
        if ($record && ($record->hasRole('Admin') || ($user && $record->id === $user->id))) return false;
        return true;
    }

    /** Indica se o toggle de aprovação de e-mail deve estar desabilitado. */
    public function desabilitarToggleAprovacaoEmail(?User $user, ?User $record): bool
    {
        return $user && $record && $user->id === $record->id;
    }

    /** Indica se a seção de vínculo com escola deve ser visível. */
    public function podeVerSecaoEscola(?User $user, ?User $record, string $context): bool
    {
        if ($context === 'create') return true;
        if (! $record) return false;
        if ($record->hasRole('Admin')) return false;
        if ($user && $record->id === $user->id) return false;
        if ($context === 'edit') return true;
        return false;
    }

    /** Indica se um registro pode ser selecionado na tabela. */
    public function podeSelecionarRegistro(?User $user, User $record): bool
    {
        if (! $this->ehAdmin($user) && $record->hasRole('Admin')) return false;
        return true;
    }

    /** Indica se pode deletar um usuário específico. */
    public function podeDeletar(?User $user, User $record): bool
    {
        if (! $user) return false;
        if ($record->id === 1) return false;
        if ($record->id === $user->id) return false;
        return $this->ehAdmin($user);
    }

    /** Indica se pode executar exclusão em lote. */
    public function podeDeletarEmLote(?User $user, iterable $records): bool
    {
        if (! $this->ehAdmin($user)) return false;
        foreach ($records as $u) {
            if ($u instanceof User && $u->hasRole('Admin')) return false;
        }
        return true;
    }

    /** Retorna o badge de navegação de novos usuários. */
    public function badgeNavegacaoParaNovosUsuarios(?User $user): ?string
    {
        if (! $user || ! $this->ehAdmin($user)) return null;

        $ignorados = IgnoredUser::where('admin_id', $user->id)->pluck('user_id')->toArray();
        $count = User::where('email_approved', false)->whereNotIn('id', $ignorados)->count();

        return $count > 0 ? (string) $count : null;
    }

    /** Sincroniza ignorados para pendentes (equivalente ao getTableQuery). */
    public function sincronizarIgnoradosParaAdmin(User $admin): void
    {
        if (! $this->ehAdmin($admin)) return;

        $pendentes = User::where('email_approved', false)->pluck('id');
        foreach ($pendentes as $userId) {
            IgnoredUser::firstOrCreate([
                'admin_id' => $admin->id,
                'user_id'  => $userId,
            ]);
        }
    }
}
