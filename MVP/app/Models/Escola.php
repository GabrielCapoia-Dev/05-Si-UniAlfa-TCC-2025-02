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
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            $parts = [];
            foreach ($validate['errors'] as $err) {
                $ln = $err['row'] ?? '?';
                $parts[] = "Linha {$ln}: " . implode(' | ', $err['messages']);
            }
            throw new \RuntimeException(
                "Importação cancelada. Há linhas com erro:\n" . implode("\n", $parts)
            );
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
            ];
        };

        $payload = array_map($mapHeaderToDb, $validate['data']);

        return DB::transaction(function () use ($payload) {
            $affected = self::upsert(
                $payload,
                ['nome'],
                ['logradouro', 'bairro', 'cidade', 'estado', 'cep', 'numero', 'complemento', 'updated_at']
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
        ];

        return response()->streamDownload(function () use ($headers) {
            $writer = SimpleExcelWriter::streamDownload('modelo-escolas.xlsx');
            $writer->addRow(array_combine($headers, array_fill(0, count($headers), '')));
            $writer->toBrowser();
        }, 'modelo-escolas.xlsx');
    }
}
