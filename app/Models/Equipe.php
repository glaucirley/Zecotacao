<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipe extends Model
{
    protected $table = 'equipes';

    protected $fillable = [
        'nome',
        'gestor_id',
    ];

    public function gestor()
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function membros()
    {
        return $this->hasMany(User::class, 'equipe_id');
    }
}
