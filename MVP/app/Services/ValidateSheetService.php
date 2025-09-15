<?php

namespace App\Services;

use Google\Service\Sheets;
use Google\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class ValidateSheetService
{
    protected Client $client;

    public function __construct(GoogleDriveService $driveService)
    {
        $user = Auth::user();
        $this->client = $driveService->getClientFor($user);
    }

    /** Garante que a planilha tem as colunas obrigatórias. */
    public function checkStructure(string $fileId, array $rules): array
    {
        [$headers] = $this->readHeadersAndRows($fileId);
        $required = $rules['requiredColumns'] ?? [];

        $missing = [];
        foreach ($required as $col) {
            if (!in_array($col, $headers, true)) {
                $missing[] = $col;
            }
        }

        return ['valid' => empty($missing), 'errors' => $missing];
    }

    /** Lê cabeçalho (A1:Z1) e todas as linhas seguintes (A2:Z). */
    public function readHeadersAndRows(string $fileId): array
    {
        $sheets = new Sheets($this->client);

        try {
            $headResp = $sheets->spreadsheets_values->get($fileId, 'A1:Z1');
            $headers  = array_map('trim', $headResp->getValues()[0] ?? []);

            $rowsResp = $sheets->spreadsheets_values->get($fileId, 'A2:Z');
            $rows     = $rowsResp->getValues() ?? [];
        } catch (\Google\Service\Exception $e) {
            $payload = json_decode($e->getMessage(), true);
            if (Arr::get($payload, 'error.code') === 403) {
                throw new RuntimeException(
                    'Permissões insuficientes para ler esta planilha (Google Sheets). Reconecte sua conta Google.'
                );
            }
            throw $e;
        }

        return [$headers, $rows];
    }

    /** Transforma as linhas em arrays associativos "header => valor". */
    public function assocRows(string $fileId): array
    {
        [$headers, $rows] = $this->readHeadersAndRows($fileId);
        $assoc = [];

        foreach ($rows as $i => $row) {
            // ignorar linhas totalmente vazias
            if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }

            $item = [];
            foreach ($headers as $idx => $header) {
                $item[$header] = $row[$idx] ?? null;
            }

            // Guarda número de linha planilha (dados iniciam na linha 2)
            $item['_row'] = $i + 2;

            $assoc[] = $item;
        }

        return $assoc;
    }

    /** Normalização leve (trim, CEP só dígitos, UF maiúscula). */
    public function normalizeRow(array $row): array
    {
        foreach ($row as $k => $v) {
            if ($k === '_row') continue;
            $row[$k] = is_string($v) ? trim($v) : $v;
        }

        // Normalizações específicas:
        if (isset($row['CEP'])) {
            $row['CEP'] = preg_replace('/\D+/', '', (string) $row['CEP']); // só dígitos
        }
        if (isset($row['Estado'])) {
            $row['Estado'] = mb_strtoupper((string) $row['Estado']);
        }

        return $row;
    }

    /**
     * Valida cada linha segundo as regras informadas (regras do Laravel Validator).
     * Retorna ['valid' => bool, 'errors' => [ ['row' => N, 'messages' => [...]], ... ], 'data' => [ ...normalizado... ] ]
     */
    public function validateRows(array $assocRows, array $rules, array $attributes = []): array
    {
        $errors = [];
        $clean  = [];

        foreach ($assocRows as $row) {
            $row = $this->normalizeRow($row);

            // O Validator espera chaves nomeadas como os campos de entrada.
            $validator = Validator::make($row, $rules, [], $attributes);

            if ($validator->fails()) {
                $errors[] = [
                    'row' => $row['_row'] ?? null,
                    'messages' => $validator->errors()->all(),
                ];
            } else {
                $clean[] = $row;
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
            'data'   => $clean,
        ];
    }
}