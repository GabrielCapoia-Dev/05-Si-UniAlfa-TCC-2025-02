<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class Turma extends Model
{
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use LogsActivity;

    protected $table = 'escolas';

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
        return $this->belongsTo(Serie::class);
    }

    public function escola()
    {
        return $this->belongsTo(Escola::class);
    }
}
