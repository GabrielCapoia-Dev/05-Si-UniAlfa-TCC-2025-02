<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Escola extends Model
{
    use HasFactory, Notifiable, LogsActivity;

    protected $table = 'escolas';

    protected $fillable = [
        'nome',
        'tipo',
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
    ];

    /** ====== LOGS (Spatie) ====== */

    /** Mapa de permissões específicas deste model */
    protected function permissionMap(): array
    {
        return [
            'viewAny' => 'Listar Escolas',
            'view'    => 'Listar Escolas',
            'create'  => 'Criar Escolas',
            'update'  => 'Editar Escolas',
            'delete'  => 'Excluir Escolas',
            'restore' => 'Restaurar Escolas',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('escolas')
            ->logOnly([
                'nome',
                'tipo',
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
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(fn(string $event) => $this->buildDescription($event));
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

        // merge metadados úteis
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
        $alvo    = $this->nome ?? "Escola #{$this->getKey()}";
        $quando  = now()->format('Y-m-d H:i:s');

        return "Usuário {$usuario}, com permissão para {$permTxt}, "
            . "realizou {$ability} na escola {$alvo}. "
            . "Operação em {$quando}.";
    }

    /** ====== Relações ====== */

    public function turmas()
    {
        return $this->hasMany(Turma::class, 'id_escola');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'id_escola');
    }

    public function rotas()
    {
        return $this->belongsToMany(Rota::class, 'escola_rota', 'escola_id', 'rota_id');
    }

    /** ====== (seu código existente) ====== */

    private static function inferFieldFromMessage(string $msg): ?string
    {
        if (preg_match('/campo\s+([^\s\.\:]+)/iu', $msg, $m)) {
            return ucfirst($m[1]); // "nome" -> "Nome"
        }
        if (preg_match('/^([^\:\.]+)\s*:/u', $msg, $m)) {
            return trim($m[1]);
        }
        foreach (['Nome', 'Logradouro', 'Bairro', 'Cidade', 'Estado', 'CEP', 'Numero', 'Número', 'Complemento', 'Tipo'] as $col) {
            if (stripos($msg, $col) !== false) return $col;
        }
        return null;
    }

    private static function categorizeMessage(string $msg): string
    {
        $m = mb_strtolower($msg, 'UTF-8');
        if (preg_match('/já está em uso|já existe|duplicad|unique|distinct/u', $m)) return 'duplicate';
        if (preg_match('/em branco|obrigatóri|required/u', $m)) return 'blank';
        if (preg_match('/formato|inv[aá]lido|regex|tamanho|size/u', $m)) return 'invalid';
        return 'invalid';
    }

    public function validateGoogleSheet(string $fileId): void
    {
        $sheetValidator = app(\App\Services\ValidateSheetService::class);
        $result = $sheetValidator->checkStructure($fileId, [
            'requiredColumns' => ['Nome', 'Logradouro', 'Bairro', 'Cidade', 'Estado', 'CEP'],
        ]);

        if (! $result['valid']) {
            throw new \RuntimeException(
                'Planilha inválida. Faltam as colunas: ' . implode(', ', $result['errors'])
            );
        }
    }

    public function importGoogleSheet(string $fileId): array
    {
        $svc = app(\App\Services\ValidateSheetService::class);

        $this->validateGoogleSheet($fileId);

        $rows = $svc->assocRows($fileId);

        if (empty($rows)) {
            return ['imported' => 0, 'updated' => 0];
        }

        $rules = [
            'Nome'       => ['required', 'string', 'max:255', 'distinct', 'unique:escolas,nome'],
            'Logradouro' => ['required', 'string', 'max:255'],
            'Bairro'     => ['required', 'string', 'max:255'],
            'Cidade'     => ['required', 'string', 'max:255'],
            'Estado'     => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'CEP'        => ['required', 'regex:/^\d{8}$/'],
            'Numero'     => ['nullable', 'string', 'max:255'],
            'Número'     => ['nullable', 'string', 'max:255'],
            'Complemento' => ['nullable', 'string', 'max:255'],
            'Tipo'       => ['required', 'string', 'max:255', 'in:Municipal,Estadual'],
        ];

        $attributes = [
            'Nome'        => 'nome',
            'Logradouro'  => 'logradouro',
            'Bairro'      => 'bairro',
            'Cidade'      => 'cidade',
            'Estado'      => 'estado',
            'CEP'         => 'cep',
            'Numero'      => 'número',
            'Número'      => 'número',
            'Complemento' => 'complemento',
            'Tipo'        => 'tipo',
        ];

        $validate = $svc->validateRows($rows, $rules, $attributes);

        if (! $validate['valid']) {
            $flags = [];

            foreach ($validate['errors'] as $err) {
                foreach ($err['messages'] as $msg) {
                    $field = self::inferFieldFromMessage($msg) ?? 'Geral';
                    $cat   = self::categorizeMessage($msg);
                    $flags[$field][$cat] = true;
                }
            }

            $labels = [
                'duplicate' => 'valores que já existem no sistema',
                'blank'     => 'valores em branco',
                'invalid'   => 'valores inválidos',
            ];

            $parts = [];
            foreach ($flags as $field => $cats) {
                $present = array_keys(array_filter($cats));
                if (! $present) continue;

                $desc = array_map(fn($c) => $labels[$c] ?? $c, $present);
                $prefix = $field === 'Geral' ? 'Geral' : "Coluna {$field}";
                $parts[] = "{$prefix} possui " . implode(', ', $desc) . '.';
            }

            throw new \RuntimeException(implode("\n", $parts));
        }

        $mapHeaderToDb = function (array $r) {
            $numero = $r['Numero'] ?? $r['Número'] ?? null;

            return [
                'nome'        => $r['Nome'],
                'logradouro'  => $r['Logradouro'],
                'bairro'      => $r['Bairro'],
                'cidade'      => $r['Cidade'],
                'estado'      => $r['Estado'],
                'cep'         => $r['CEP'],
                'numero'      => $numero,
                'complemento' => $r['Complemento'] ?? null,
                'tipo'        => $r['Tipo'],
            ];
        };

        $payload = array_map($mapHeaderToDb, $validate['data']);

        return DB::transaction(function () use ($payload) {
            $affected = self::upsert(
                $payload,
                ['nome'],
                ['logradouro', 'bairro', 'cidade', 'estado', 'cep', 'numero', 'complemento', 'tipo', 'updated_at']
            );

            return ['imported_or_updated' => $affected];
        });
    }

    public static function exportModelo(): StreamedResponse
    {
        $headers = [
            'Nome',
            'Logradouro',
            'Bairro',
            'Cidade',
            'Estado',
            'CEP',
            'Numero',
            'Complemento',
            'Tipo',
        ];

        return response()->streamDownload(function () use ($headers) {
            $writer = SimpleExcelWriter::streamDownload('modelo-escolas.xlsx');

            $writer->addRow(array_combine($headers, array_fill(0, count($headers), '')));

            $writer->addRow([
                'Nome'        => 'Escola Municipal Monteiro Lobato',
                'Logradouro'  => 'Rua das Flores',
                'Bairro'      => 'Centro',
                'Cidade'      => 'Umuarama',
                'Estado'      => 'PR',
                'CEP'         => '87501000',
                'Numero'      => '123',
                'Complemento' => 'Próximo à praça',
                'Tipo'        => 'Municipal',
            ]);

            $writer->addRow([
                'Nome'        => 'Escola Municipal Machado de Assis',
                'Logradouro'  => 'Avenida Brasil',
                'Bairro'      => 'Jardim América',
                'Cidade'      => 'Maringá',
                'Estado'      => 'PR',
                'CEP'         => '87000000',
                'Numero'      => '456',
                'Complemento' => '',
                'Tipo'        => 'Estadual',
            ]);

            $writer->toBrowser();
        }, 'modelo-escolas.xlsx');
    }
}
