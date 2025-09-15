<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Escola extends Model
{
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use LogsActivity;

    protected $table = 'escolas';

    protected $fillable = [
        'nome',
        'latitude',
        'longitude',
        'raio',
        'logradouro',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'numero',
        'complemento'
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nome',
                'latitude',
                'longitude',
                'raio',
                'logradouro',
                'bairro',
                'cidade',
                'estado',
                'cep',
            ]);
    }

    public function turmas()
    {
        return $this->hasMany(Turma::class);
    }

    /** Valida apenas cabeçalho obrigatório (já existia). */
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

    /**
     * Valida TODAS as linhas e importa (upsert por 'nome').
     * Retorna contagem de linhas importadas/atualizadas.
     */
    public function importGoogleSheet(string $fileId): array
    {
        $svc = app(\App\Services\ValidateSheetService::class);

        // 1) estrutura (cabeçalho)
        $this->validateGoogleSheet($fileId);

        // 2) linhas associativas
        $rows = $svc->assocRows($fileId);

        if (empty($rows)) {
            return ['imported' => 0, 'updated' => 0];
        }

        // 3) regras por linha (compatíveis com sua migration)
        $rules = [
            'Nome'       => ['required', 'string', 'max:255', 'distinct', 'unique:escolas,nome'],
            'Logradouro' => ['required', 'string', 'max:255'],
            'Bairro'     => ['required', 'string', 'max:255'],
            'Cidade'     => ['required', 'string', 'max:255'],
            'Estado'     => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'CEP'        => ['required', 'regex:/^\d{8}$/'],
            'Numero'     => ['nullable', 'string', 'max:255'],
            'Número'     => ['nullable', 'string', 'max:255'], // aceita "Número" também
            'Complemento' => ['nullable', 'string', 'max:255'],
        ];

        $attributes = [
            'Nome' => 'nome',
            'Logradouro' => 'logradouro',
            'Bairro' => 'bairro',
            'Cidade' => 'cidade',
            'Estado' => 'estado',
            'CEP' => 'cep',
            'Numero' => 'número',
            'Número' => 'número',
            'Complemento' => 'complemento',
        ];

        $validate = $svc->validateRows($rows, $rules, $attributes);

        if (! $validate['valid']) {
            // Monta mensagem amigável com linha + erros
            $parts = [];
            foreach ($validate['errors'] as $err) {
                $ln = $err['row'] ?? '?';
                $parts[] = "Linha {$ln}: " . implode(' | ', $err['messages']);
            }
            throw new \RuntimeException(
                "Importação cancelada. Há linhas com erro:\n" . implode("\n", $parts)
            );
        }

        // 4) Mapear cabeçalhos => colunas do DB
        $mapHeaderToDb = function (array $r) {
            // preferir 'Numero' mas aceitar 'Número'
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
                // latitude/longitude/raio NÃO vêm da planilha (ficam null)
            ];
        };

        $payload = array_map($mapHeaderToDb, $validate['data']);

        // 5) Import atômico (se algo falhar, nada é salvo)
        return DB::transaction(function () use ($payload) {
            // Se quiser evitar updates, troque por: self::insert($payload)
            // Aqui vamos de UPSERT para garantir idempotência por 'nome'
            $affected = self::upsert(
                $payload,
                ['nome'], // constraint única
                ['logradouro', 'bairro', 'cidade', 'estado', 'cep', 'numero', 'complemento', 'updated_at']
            );

            // O upsert retorna número de linhas afetadas (insert + update)
            // Não conseguimos separar facilmente insert de update sem consulta extra;
            // então devolvo tudo em 'affected'.
            return ['imported_or_updated' => $affected];
        });
    }
}