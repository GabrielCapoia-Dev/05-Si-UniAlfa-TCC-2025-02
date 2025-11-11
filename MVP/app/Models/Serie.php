<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Traits\HasRoles;

class Serie extends Model
{
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $fillable = ['nome'];

    protected function permissionMap(): array
    {
        return [
            'viewAny' => 'Listar Séries',
            'view'    => 'Listar Séries',
            'create'  => 'Criar Séries',
            'update'  => 'Editar Séries',
            'delete'  => 'Excluir Séries',
            'restore' => 'Restaurar Séries',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('series')
            ->logOnly(['nome'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(fn(string $event) => $this->buildDescription($event));
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $ability = ['created' => 'create', 'updated' => 'update', 'deleted' => 'delete', 'restored' => 'restore'][$eventName] ?? 'update';
        $perm    = $this->permissionMap()[$ability] ?? null;

        $props = $activity->properties ?? [];
        if ($props instanceof \Illuminate\Support\Collection) $props = $props->toArray();

        $activity->properties = collect((array) $props)->merge([
            'ability'             => $ability,
            'policy_permission'   => $perm,
            'user_has_permission' => $perm && Auth::user() ? Auth::user()->hasPermissionTo($perm) : null,
            'when'                => now()->toDateTimeString(),
            'subject_labels'      => ['serie' => $this->nome ?? "Série #{$this->getKey()}"],
        ]);

        if ($u = Auth::user()) $activity->causedBy($u);
    }

    protected function buildDescription(string $event): string
    {
        $ability = ['created' => 'create', 'updated' => 'update', 'deleted' => 'delete', 'restored' => 'restore'][$event] ?? 'update';
        $usuario = Auth::user()->name ?? 'Sistema';
        $permTxt = $this->permissionMap()[$ability] ?? 'sem permissão identificada';
        $alvo    = $this->nome ?? "Série #{$this->getKey()}";
        $quando  = now()->format('Y-m-d H:i:s');

        return "Usuário {$usuario}, com permissão para {$permTxt}, realizou {$ability} na série {$alvo}. Operação em {$quando}.";
    }
}
