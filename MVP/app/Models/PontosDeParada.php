<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class PontosDeParada extends Model
{
    use HasFactory, Notifiable, LogsActivity;

    protected $table = 'pontos_de_parada';

    protected $fillable = [
        'id_rota',
        'id_escola',
        'latitude',
        'longitude',
        'ordem',
        'tipo',
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
        'ordem'     => 'integer',
    ];

    /** ===== Permissões deste model ===== */
    protected function permissionMap(): array
    {
        return [
            'viewAny' => 'Listar Pontos de Parada',
            'view'    => 'Listar Pontos de Parada',
            'create'  => 'Criar Pontos de Parada',
            'update'  => 'Editar Pontos de Parada',
            'delete'  => 'Excluir Pontos de Parada',
            'restore' => 'Restaurar Pontos de Parada',
        ];
    }

    /** ===== Spatie Activitylog ===== */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('pontos_de_parada')
            ->logOnly(['id_rota', 'id_escola', 'latitude', 'longitude', 'ordem', 'tipo'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(fn (string $event) => $this->buildDescription($event));
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $ability = [
            'created'  => 'create',
            'updated'  => 'update',
            'deleted'  => 'delete',
            'restored' => 'restore',
        ][$eventName] ?? 'update';

        $perm = $this->permissionMap()[$ability] ?? null;

        // carrega rótulos de contexto (rota/escola) para o log
        $rotaNome   = optional($this->relationLoaded('rota') ? $this->rota : $this->rota()->first())->nome ?? null;
        $escolaNome = optional($this->relationLoaded('escola') ? $this->escola : $this->escola()->first())->nome ?? null;

        // normaliza properties -> array
        $props = $activity->properties ?? [];
        if ($props instanceof \Illuminate\Support\Collection) $props = $props->toArray();
        elseif (is_string($props)) { try { $props = json_decode($props, true, 512, JSON_THROW_ON_ERROR) ?: []; } catch (\Throwable) { $props = []; } }
        elseif (! is_array($props)) $props = (array) $props;

        $activity->properties = collect($props)->merge([
            'ability'             => $ability,
            'policy_permission'   => $perm,
            'user_has_permission' => $perm && Auth::user() ? Auth::user()->hasPermissionTo($perm) : null,
            'ip'                  => app()->runningInConsole() ? null : request()?->ip(),
            'user_agent'          => app()->runningInConsole() ? null : request()?->userAgent(),
            'when'                => now()->toDateTimeString(),
            'subject_labels'      => [
                'ponto'  => $this->ordem ? "Ponto {$this->ordem}" : "Ponto #{$this->getKey()}",
                'rota'   => $rotaNome,
                'escola' => $escolaNome,
            ],
        ]);

        if ($u = Auth::user()) {
            $activity->causedBy($u);
        }
    }

    protected function buildDescription(string $event): string
    {
        $ability = [
            'created'  => 'create',
            'updated'  => 'update',
            'deleted'  => 'delete',
            'restored' => 'restore',
        ][$event] ?? 'update';

        $usuario    = Auth::user()->name ?? 'Sistema';
        $permTxt    = $this->permissionMap()[$ability] ?? 'sem permissão identificada';
        $ordemTxt   = $this->ordem ? "Ponto {$this->ordem}" : "Ponto #{$this->getKey()}";
        $rotaNome   = optional($this->relationLoaded('rota') ? $this->rota : $this->rota()->first())->nome ?? null;
        $escolaNome = optional($this->relationLoaded('escola') ? $this->escola : $this->escola()->first())->nome ?? null;
        $ctx        = [];
        if ($rotaNome)   $ctx[] = "rota {$rotaNome}";
        if ($escolaNome) $ctx[] = "escola {$escolaNome}";
        $ctxStr     = $ctx ? ' ('.implode(' · ', $ctx).')' : '';
        $quando     = now()->format('Y-m-d H:i:s');

        return "Usuário {$usuario}, com permissão para {$permTxt}, "
             . "realizou {$ability} em {$ordemTxt}{$ctxStr}. Operação em {$quando}.";
    }

    /** ===== Relações ===== */
    public function rota()
    {
        return $this->belongsTo(Rota::class, 'id_rota');
    }

    public function escola()
    {
        return $this->belongsTo(Escola::class, 'id_escola');
    }
}
