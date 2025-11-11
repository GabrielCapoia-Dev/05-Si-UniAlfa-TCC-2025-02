<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Turma extends Model
{
    use HasFactory, Notifiable, LogsActivity;

    protected $table = 'turmas';

    protected $fillable = [
        'id_serie',
        'id_escola',
        'turma',   // letra (ex.: A, B...)
        'turno',
    ];

    protected function permissionMap(): array
    {
        return [
            'viewAny' => 'Listar Turmas',
            'view'    => 'Listar Turmas',
            'create'  => 'Criar Turmas',
            'update'  => 'Editar Turmas',
            'delete'  => 'Excluir Turmas',
            'restore' => 'Restaurar Turmas',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('turmas')
            ->logOnly(['id_serie', 'id_escola', 'turma', 'turno'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(fn(string $event) => $this->buildDescription($event));
    }

    /** Nome amigável no padrão "SÉRIE + TURMA" → ex.: "1º Ano A" */
    public function getDisplayName(): string
    {
        $serieNome = optional($this->relationLoaded('serie') ? $this->serie : $this->serie()->first())->nome;
        $letra     = $this->turma ?: '';
        $label     = trim(($serieNome ?: "Série #{$this->id_serie}") . ' ' . $letra);
        return $label !== '' ? $label : "Turma #{$this->getKey()}";
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $ability   = ['created' => 'create', 'updated' => 'update', 'deleted' => 'delete', 'restored' => 'restore'][$eventName] ?? 'update';
        $perm      = $this->permissionMap()[$ability] ?? null;

        // carrega rótulos de contexto
        $serieNome  = optional($this->relationLoaded('serie') ? $this->serie : $this->serie()->first())->nome;
        $escolaNome = optional($this->relationLoaded('escola') ? $this->escola : $this->escola()->first())->nome;

        $turmaLabel = $this->getDisplayName();

        // normaliza properties -> array
        $props = $activity->properties ?? [];
        if ($props instanceof \Illuminate\Support\Collection) $props = $props->toArray();

        $activity->properties = collect((array) $props)->merge([
            'ability'             => $ability,
            'policy_permission'   => $perm,
            'user_has_permission' => $perm && Auth::user() ? Auth::user()->hasPermissionTo($perm) : null,
            'when'                => now()->toDateTimeString(),
            'subject_labels'      => [
                'turma'  => $turmaLabel,              // << aqui vai "1º Ano A"
                'serie'  => $serieNome,
                'escola' => $escolaNome,
            ],
        ]);

        if ($u = Auth::user()) $activity->causedBy($u);
    }

    protected function buildDescription(string $event): string
    {
        $ability   = ['created' => 'create', 'updated' => 'update', 'deleted' => 'delete', 'restored' => 'restore'][$event] ?? 'update';
        $usuario   = Auth::user()->name ?? 'Sistema';
        $permTxt   = $this->permissionMap()[$ability] ?? 'sem permissão identificada';
        $alvo      = $this->getDisplayName(); // "1º Ano A"
        $escola    = optional($this->relationLoaded('escola') ? $this->escola : $this->escola()->first())->nome;
        $ctx       = $escola ? " (escola {$escola})" : '';
        $quando    = now()->format('Y-m-d H:i:s');

        return "Usuário {$usuario}, com permissão para {$permTxt}, "
            . "realizou {$ability} na turma {$alvo}{$ctx}. Operação em {$quando}.";
    }

    /* ===== Relações ===== */
    public function serie()
    {
        return $this->belongsTo(Serie::class, 'id_serie');
    }

    public function escola()
    {
        return $this->belongsTo(Escola::class, 'id_escola');
    }

    public function alunos()
    {
        return $this->hasMany(Aluno::class, 'id_turma');
    }
}
