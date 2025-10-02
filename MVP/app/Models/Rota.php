<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Rota extends Model
{
    use HasFactory;
    use Notifiable;
    use LogsActivity;

    protected $table = 'rotas';
    protected $fillable = [
        'nome',
        'turno',
    ];



    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nome',
                'turno',
            ]);
    }

    public function alunos()
    {
        return $this->hasMany(Aluno::class);
    }

    public function pontosDeParada()
    {
        return $this->hasMany(PontosDeParada::class);
    }

    public function escolas()
    {
        return $this->belongsToMany(Escola::class, 'escola_rota', 'rota_id', 'escola_id');
    }
}
