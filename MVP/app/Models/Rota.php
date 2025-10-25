<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Rota extends Model
{
    use HasFactory, Notifiable, LogsActivity;

    protected $table = 'rotas';

    protected $fillable = [
        'nome',
        'turno',
        'distancia_total',
        'tempo_estimado',
        'geometry',
        'waypoints',
        'legs',
        'valor_por_km',
        'valor_total',
    ];

    protected $casts = [
        'distancia_total' => 'float',
        'tempo_estimado'  => 'integer',
        'geometry'        => 'array',
        'waypoints'       => 'array',
        'legs'            => 'array',
        'valor_por_km'    => 'float',
        'valor_total'     => 'float',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nome', 'turno', 'distancia_total', 'tempo_estimado'])
            ->dontLogIfAttributesChangedOnly(['geometry', 'waypoints', 'legs']);
    }

    public function alunos()
    {
        return $this->hasMany(Aluno::class);
    }

    public function pontosDeParada()
    {
        return $this->hasMany(PontosDeParada::class, 'id_rota');
    }

    public function escolas()
    {
        return $this->belongsToMany(Escola::class, 'escola_rota', 'rota_id', 'escola_id');
    }
}
