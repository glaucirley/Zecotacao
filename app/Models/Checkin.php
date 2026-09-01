<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Checkin extends Model
{
    use HasFactory;

    protected $table = 'checkins';

    protected $fillable = [
        'usuario_id',
        'parceiro_id',
        'latitude',
        'longitude',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function parceiro()
    {
        return $this->belongsTo(Parceiro::class, 'parceiro_id');
    }
}
