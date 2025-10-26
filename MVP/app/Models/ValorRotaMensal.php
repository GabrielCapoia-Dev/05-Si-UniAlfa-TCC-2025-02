<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValorRotaMensal extends Model
{
    protected $table = 'valor_rota_mensals';


    protected $fillable = [
        'mes',
        'ano',
        'valor_total_mes',
        'valor_total_por_rota',
        'valor_total_por_turno'
    ];

    protected $casts = [
        'mes' => 'integer',
        'ano' => 'integer',
        'valor_total_mes' => 'integer',
        'valor_total_por_rota' => 'array',
        'valor_total_por_turno' => 'array'
    ];
}
