<?php

namespace App\Models;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $fillable = [
        'id_escola',
        'name',
        'email',
        'email_approved',
        'email_verified_at',
        'password',
        'google_id',
        'google_email',
        'google_token',
        'google_refresh_token',
        'google_token_expires_in',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'google_refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'google_token_expires_in' => 'datetime',
            'email_approved'          => 'boolean', // mantém como boolean no model
        ];
    }

    protected function permissionMap(): array
    {
        return [
            'viewAny' => 'Listar Usuários',
            'view'    => 'Listar Usuários',
            'create'  => 'Criar Usuários',
            'update'  => 'Editar Usuários',
            'delete'  => 'Excluir Usuários',
            'restore' => 'Restaurar Usuários',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('usuarios')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->logExcept([
                'google_token',
                'google_refresh_token',
                'google_token_expires_in',
                'remember_token',
                'updated_at',
                'password',
            ])
            ->setDescriptionForEvent(fn(string $event) => $this->buildDescription($event));
    }

    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName): void
    {
        // 1) ability & perm do model User
        $abilityMap = ['created' => 'create', 'updated' => 'update', 'deleted' => 'delete', 'restored' => 'restore'];
        $ability    = $abilityMap[$eventName] ?? 'update';

        $permMap = [
            'create'  => 'Criar Usuários',
            'update'  => 'Editar Usuários',
            'delete'  => 'Excluir Usuários',
            'restore' => 'Restaurar Usuários',
            'view'    => 'Listar Usuários',
            'viewAny' => 'Listar Usuários',
        ];
        $permLabel = $permMap[$ability] ?? null;

        $user = \Illuminate\Support\Facades\Auth::user();
        $hasPerm = null;
        if ($user && $permLabel) {
            try {
                $hasPerm = $user->hasPermissionTo($permLabel);
            } catch (\Throwable) {
                $hasPerm = null;
            }
        }

        // 2) normaliza properties -> array
        $props = $activity->properties;
        if ($props instanceof \Illuminate\Support\Collection) {
            $props = $props->toArray();
        } elseif (is_object($props) && method_exists($props, 'toArray')) {
            $props = $props->toArray();
        } elseif (is_string($props)) {
            try {
                $props = json_decode($props, true, 512, JSON_THROW_ON_ERROR) ?: [];
            } catch (\Throwable) {
                $props = [];
            }
        } elseif (! is_array($props)) {
            $props = (array) $props;
        }

        // 3) traduz email_approved nos diffs
        $toBool = static function ($v): ?bool {
            if (is_bool($v)) return $v;
            if (is_int($v))  return $v === 1;
            if (is_float($v)) return (int)$v === 1;
            if ($v === null) return null;
            $s = trim(mb_strtolower((string) $v, 'UTF-8'));
            if (in_array($s, ['1', 'true', 'on', 'yes', 'sim', 'y', 't'], true))  return true;
            if (in_array($s, ['0', 'false', 'off', 'no', 'não', 'nao', 'n', 'f', ''], true)) return false;
            return filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        };
        $toLabel = static fn(?bool $b): ?string => $b === null ? null : ($b ? 'Aprovado' : 'Não Aprovado');

        foreach (['old', 'attributes'] as $bag) {
            if (isset($props[$bag]) && is_array($props[$bag]) && array_key_exists('email_approved', $props[$bag])) {
                $b = $toBool($props[$bag]['email_approved']);
                if ($b !== null) {
                    $props[$bag]['email_approved'] = $toLabel($b);
                }
            }
        }

        // 4) grava metadados de permissão
        $props['ability']             = $ability;
        if ($permLabel !== null) {
            $props['policy_permission']   = $permLabel;
            $props['user_has_permission'] = $hasPerm;
        }

        // 5) reatribui e vincula usuário
        $activity->properties = $props;
        if ($user) $activity->causedBy($user);
    }
    
    protected function buildDescription(string $event): string
    {
        $ability = ['created' => 'create', 'updated' => 'update', 'deleted' => 'delete', 'restored' => 'restore'][$event] ?? 'update';
        $perm    = $this->permissionMap()[$ability] ?? null;

        $usuario = Auth::user()->name ?? 'Sistema';
        $alvo    = $this->name ?? "#{$this->getKey()}";
        $quando  = now()->format('Y-m-d H:i:s');
        $permTxt = $perm ?: 'sem permissão identificada';

        return "Usuário {$usuario}, com permissão para {$permTxt}, realizou {$ability} no usuário {$alvo}. Operação em {$quando}.";
    }

    public function getDisplayName(): string
    {
        $email = $this->email ?? $this->google_email ?? null;
        return $email ? "{$this->name} <{$email}>" : ($this->name ?? "Usuário #{$this->getKey()}");
    }

    // Só pode acessar o painel se o email estiver aprovado
    public function canAccessPanel(Panel $panel, ?bool $register = false): bool
    {
        if ($this->email_approved == true) {
            return true;
        }
        //Força logout se não estiver aprovado
        Filament::auth()->logout();

        // Verifica se quem esta tentando acessar o painel esta vindo de um registro
        if ($register) {
            Notification::make()
                ->title('Cadastro Realizado')
                ->body('Usuário cadastrado com sucesso. Solicite aprovação do administrador para acessar o painel.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Aguardando Aprovação')
                ->body('Seu cadastro foi encaminhado para aprovação.')
                ->icon('heroicon-o-arrow-path')
                ->duration(10000)
                ->warning()
                ->send();
        }

        $loginRouteName = "filament.{$panel->getId()}.auth.login";
        $loginUrl = route($loginRouteName);

        throw new HttpResponseException(new RedirectResponse($loginUrl));
    }

    public function validateAccessGoogle(?string $register, ?string $login): bool
    {
        return $this->email_approved;
    }

    protected static function booted()
    {
        parent::booted();

        static::updating(function ($user) {
            if (
                $user->isDirty('email_approved') &&
                $user->email_approved &&
                is_null($user->getOriginal('email_verified_at'))
            ) {
                $user->email_verified_at = now();
            }
        });
    }

    public function hasGoogleOauth(): bool
    {
        return filled($this->google_token) || filled($this->google_refresh_token);
    }

    public function googleAccessTokenExpired(): bool
    {
        return is_null($this->google_token_expires_in)
            ? true
            : now()->greaterThan($this->google_token_expires_in);
    }

    public function escola()
    {
        return $this->belongsTo(Escola::class, 'id_escola');
    }
}
