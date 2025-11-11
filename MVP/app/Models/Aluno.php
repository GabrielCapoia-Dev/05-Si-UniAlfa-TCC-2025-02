<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Aluno extends Model
{
    use HasFactory, Notifiable, LogsActivity;

    protected $table = 'alunos';

    protected $fillable = [
        'id_rota',
        'id_turma',
        'nome',
        'data_nascimento',
        'cgm',
        'sexo',
        'foto',
        'nome_responsavel',
        'telefone_responsavel',
        'telefone_aluno',
        'telefone_alternativo',
        'latitude',
        'longitude',
        'raio',
        'logradouro',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'numero',
        'complemento',
        'tem_carteirinha',
    ];

    /** ===== Permissões deste model (ajuste conforme sua Policy) ===== */
    protected function permissionMap(): array
    {
        return [
            'viewAny' => 'Listar Alunos',
            'view'    => 'Listar Alunos',
            'create'  => 'Criar Alunos',
            'update'  => 'Editar Alunos',
            'delete'  => 'Excluir Alunos',
            'restore' => 'Restaurar Alunos',
        ];
    }

    /** ===== Spatie Activitylog ===== */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('alunos')
            ->logOnly($this->fillable)         // mantém tudo que é fillable
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->logExcept(['foto', 'updated_at']) // evita ruído no log
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

        // carrega rótulos de contexto
        [$turmaLabel, $serieNome, $escolaNome] = $this->resolveTurmaEscolaLabels();

        // normaliza properties -> array
        $props = $activity->properties ?? [];
        if ($props instanceof \Illuminate\Support\Collection) $props = $props->toArray();
        elseif (is_string($props)) { try { $props = json_decode($props, true, 512, JSON_THROW_ON_ERROR) ?: []; } catch (\Throwable) { $props = []; } }
        elseif (! is_array($props)) $props = (array) $props;

        $activity->properties = collect($props)->merge([
            'ability'             => $ability,
            'policy_permission'   => $perm,
            'user_has_permission' => $perm && Auth::user() ? Auth::user()->hasPermissionTo($perm) : null,
            'when'                => now()->toDateTimeString(),
            'ip'                  => app()->runningInConsole() ? null : request()?->ip(),
            'user_agent'          => app()->runningInConsole() ? null : request()?->userAgent(),
            'subject_labels'      => [
                'aluno'  => $this->nome ?? "Aluno #{$this->getKey()}",
                'turma'  => $turmaLabel,          // << "1º Ano A"
                'serie'  => $serieNome,
                'escola' => $escolaNome,
            ],
        ]);

        if ($u = Auth::user()) {
            $activity->causedBy($u);
        }
    }

    protected function buildDescription(string $event): string
    {
        $ability   = ['created'=>'create','updated'=>'update','deleted'=>'delete','restored'=>'restore'][$event] ?? 'update';
        $usuario   = Auth::user()->name ?? 'Sistema';
        $permTxt   = $this->permissionMap()[$ability] ?? 'sem permissão identificada';
        $aluno     = $this->nome ?? "Aluno #{$this->getKey()}";
        [$turmaLabel, , $escolaNome] = $this->resolveTurmaEscolaLabels();

        $escolaTxt = $escolaNome ? ", da escola {$escolaNome}" : '';
        $turmaTxt  = $turmaLabel ? ", turma {$turmaLabel}"     : '';
        $quando    = now()->format('Y-m-d H:i:s');

        return "Usuário {$usuario}, com permissão para {$permTxt}, "
             . "realizou {$ability} no aluno {$aluno}{$escolaTxt}{$turmaTxt}. "
             . "Operação em {$quando}.";
    }

    /** ===== Relações ===== */
    public function turma()
    {
        return $this->belongsTo(Turma::class, 'id_turma');
    }

    public function rota()
    {
        return $this->belongsTo(Rota::class, 'id_rota');
    }

    /** Helper: retorna [turmaLabel, serieNome, escolaNome] */
    protected function resolveTurmaEscolaLabels(): array
    {
        $turma   = $this->relationLoaded('turma') ? $this->turma : $this->turma()->with(['serie','escola'])->first();

        $turmaLabel = null;
        $serieNome  = null;
        $escolaNome = null;

        if ($turma) {
            // usa o mesmo padrão da Turma: "SÉRIE + LETRA"
            $serieNome  = optional($turma->serie)->nome;
            $letra      = $turma->turma ?: '';
            $turmaLabel = trim(($serieNome ?: "Série #{$turma->id_serie}") . ' ' . $letra) ?: null;
            $escolaNome = optional($turma->escola)->nome;
        }

        return [$turmaLabel, $serieNome, $escolaNome];
    }
}
