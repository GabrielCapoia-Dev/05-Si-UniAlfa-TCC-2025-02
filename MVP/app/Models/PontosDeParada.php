<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class PontosDeParada extends Model
{
    use HasFactory, Notifiable, HasRoles;

    protected $table = 'pontos_de_parada';

    protected $fillable = [
        'nome',
        'descricao',
        'latitude',
        'longitude',
        'raio',
        'logradouro',
        'bairro',
        'cidade',
        'uf',
        'cep',
        'tipo',
        'ativo',
    ];
}
