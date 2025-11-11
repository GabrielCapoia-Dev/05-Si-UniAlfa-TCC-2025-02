<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Rota extends Model
{
    use HasFactory, Notifiable, LogsActivity;

    protected $table = 'rotas';

    protected $fillable = [
        'nome',
        'turno',
        'distancia_total',
        'tempo_estimado',
        'geometry',
        'waypoints',
        'legs',
        'valor_por_km',
        'valor_total',
    ];

    protected $casts = [
        'distancia_total' => 'float',
        'tempo_estimado'  => 'integer',
        'geometry'        => 'array',
        'waypoints'       => 'array',
        'legs'            => 'array',
        'valor_por_km'    => 'float',
        'valor_total'     => 'float',
    ];

    /** ===== Permissões deste model (ajuste conforme Policy) ===== */
    protected function permissionMap(): array
    {
        return [
            'viewAny' => 'Listar Rotas',
            'view'    => 'Listar Rotas',
            'create'  => 'Criar Rotas',
            'update'  => 'Editar Rotas',
            'delete'  => 'Excluir Rotas',
            'restore' => 'Restaurar Rotas',
        ];
    }

    /** ===== Spatie Activitylog ===== */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('rotas')
            ->logOnly(['nome', 'turno', 'distancia_total', 'tempo_estimado', 'valor_por_km', 'valor_total'])
            ->dontLogIfAttributesChangedOnly(['geometry', 'waypoints', 'legs', 'updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
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
                'rota' => $this->nome ?? "Rota #{$this->getKey()}",
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

        $permTxt = $this->permissionMap()[$ability] ?? 'sem permissão identificada';
        $usuario = Auth::user()->name ?? 'Sistema';
        $alvo    = $this->nome ?? "Rota #{$this->getKey()}";
        $quando  = now()->format('Y-m-d H:i:s');

        return "Usuário {$usuario}, com permissão para {$permTxt}, realizou {$ability} na rota {$alvo}. Operação em {$quando}.";
    }

    /** ===== Relações ===== */
    public function alunos()
    {
        return $this->hasMany(Aluno::class, 'id_rota', 'id');
    }

    public function pontosDeParada()
    {
        return $this->hasMany(PontosDeParada::class, 'id_rota');
    }

    public function escolas()
    {
        return $this->belongsToMany(Escola::class, 'escola_rota', 'rota_id', 'escola_id');
    }
}
