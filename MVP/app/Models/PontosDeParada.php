<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class PontosDeParada extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'pontos_de_parada';

    protected $fillable = [
        'id_rota',
        'id_escola',
        'latitude',
        'longitude',
        'ordem',
        'tipo',
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
        'ordem'     => 'integer',
    ];


    /** ===== Relações ===== */
    public function rota()
    {
        return $this->belongsTo(Rota::class, 'id_rota');
    }

    public function escola()
    {
        return $this->belongsTo(Escola::class, 'id_escola');
    }
}
