<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Aluno extends Model
{

    use HasFactory;
    use Notifiable;
    use LogsActivity;

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


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'id_rota',
                'id_turma',
                'nome',
                'data_nascimento',
                'cgm',
                'sexo',
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
            ]);
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class, 'id_turma');
    }

    public function rota()
    {
        return $this->belongsTo(Rota::class, 'id_rota');
    }

    public function getEnderecoAttribute(): string
    {
        $partes = [];
        if ($this->logradouro) $partes[] = $this->logradouro . ($this->numero ? ", {$this->numero}" : '');
        if ($this->bairro)     $partes[] = $this->bairro;
        $cidadeUf = trim(($this->cidade ?? '') . '/' . ($this->estado ?? ''), '/');
        if ($cidadeUf !== '/') $partes[] = $cidadeUf;
        return implode(' - ', $partes) ?: '-';
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (filled($this->foto)) {
            $public = public_path('storage/' . ltrim($this->foto, '/'));
            if (file_exists($public)) return $public;
        }
        return public_path('img/placeholder-foto.jpg');
    }
}
