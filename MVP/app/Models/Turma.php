<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Turma extends Model
{
    use HasFactory;
    use Notifiable;
    use LogsActivity;

    protected $table = 'turmas';

    protected $fillable = [
        'id_serie',
        'id_escola',
        'turma',
        'turno',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'id_serie',
                'id_escola',
                'turma',
                'turno',
            ]);
    }


    public function serie()
    {
        return $this->belongsTo(Serie::class, 'id_serie');
    }

    public function escola()
    {
        return $this->belongsTo(Escola::class, 'id_escola');
    }

    public function alunos()
    {
        return $this->hasMany(Aluno::class, 'id_turma');
    }
}