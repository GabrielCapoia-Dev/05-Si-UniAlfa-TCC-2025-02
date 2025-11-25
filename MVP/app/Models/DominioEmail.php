<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class DominioEmail extends Model
{
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $table = 'dominio_emails';

    protected $fillable = [
        'dominio_email',
        'setor',
        'status', // 0/1, false/true, 'ativo'/'inativo'… (ver mapeamento abaixo)
    ];

    /** Mapa de permissões para este model */
    protected function permissionMap(): array
    {
        return [
            'viewAny' => 'Listar Domínios de Email',
            'view'    => 'Listar Domínios de Email',
            'create'  => 'Criar Domínios de Email',
            'update'  => 'Editar Domínios de Email',
            'delete'  => 'Excluir Dominios de Email',
            'restore' => 'Restaurar Domínios de Email',
        ];
    }

    /** Opções do Spatie Activitylog */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('dominio_emails')
            ->logOnly(['dominio_email', 'setor', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(fn(string $event) => $this->buildDescription($event));
    }

    /** Preenche metadados e traduz status antes de salvar o log */
    public function tapActivity(Activity $activity, string $eventName): void
    {
        $ability = ['created' => 'create', 'updated' => 'update', 'deleted' => 'delete', 'restored' => 'restore'][$eventName] ?? 'update';
        $perm    = $this->permissionMap()[$ability] ?? null;

        // normaliza properties em array
        $props = $activity->properties ?? [];
        if ($props instanceof \Illuminate\Support\Collection) {
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

        // Traduz 'status' (old/attributes) para "Ativo / Inativo"
        $toLabel = function ($v) {
            if ($v === null || $v === '') return '—';
            $s = strtolower((string) $v);
            return in_array($s, ['1', 'true', 'on', 'yes', 'sim', 'ativo', 'atvo'], true) ? 'Ativo' : 'Inativo';
        };
        foreach (['old', 'attributes'] as $bag) {
            if (isset($props[$bag]['status'])) {
                $props[$bag]['status'] = $toLabel($props[$bag]['status']);
            }
        }

        // merge final
        $activity->properties = collect($props)->merge([
            'ability'             => $ability,
            'policy_permission'   => $perm,
            'user_has_permission' => $perm && Auth::user() ? Auth::user()->hasPermissionTo($perm) : null,
            'ip'                  => app()->runningInConsole() ? null : request()?->ip(),
            'user_agent'          => app()->runningInConsole() ? null : request()?->userAgent(),
            'when'                => now()->toDateTimeString(),
        ]);

        if ($u = Auth::user()) {
            $activity->causedBy($u);
        }
    }

    /** Descrição enxuta e clara */
    protected function buildDescription(string $event): string
    {
        $ability = ['created' => 'create', 'updated' => 'update', 'deleted' => 'delete', 'restored' => 'restore'][$event] ?? 'update';
        $perm    = $this->permissionMap()[$ability] ?? null;

        $usuario = Auth::user()->name ?? 'Sistema';
        $alvo    = $this->dominio_email ?? "#{$this->getKey()}";
        $quando  = now()->format('Y-m-d H:i:s');
        $permTxt = $perm ?: 'sem permissão identificada';

        // inclui setor quando houver
        $setorTxt = $this->setor ? " (setor: {$this->setor})" : '';

        return "Usuário {$usuario}, com permissão para {$permTxt}, "
            . "realizou {$ability} no domínio de e-mail {$alvo}{$setorTxt}. "
            . "Operação em {$quando}.";
    }
}
