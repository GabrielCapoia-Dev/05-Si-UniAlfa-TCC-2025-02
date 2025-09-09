<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;


class PontosDeParada extends Model

{
    use HasFactory;
    use Notifiable;
    use HasRoles;

    protected $table = 'pontos_de_paradas';

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
    ];
}