<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class Aluno extends Model
{

    use HasFactory;
    use Notifiable;
    use HasRoles;
    use LogsActivity;

    protected $table = 'alunos';

    protected $fillable = [
        // 'id_serie',
        // 'id_escola',
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
        'complemento'
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                // 'id_serie',
                // 'id_escola',
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
                'complemento'
            ]);
    }


    // public function serie()
    // {
    //     return $this->belongsTo(Serie::class, 'id_serie');
    // }

    // public function escola()
    // {
    //     return $this->belongsTo(Escola::class, 'id_escola');
    // }
}