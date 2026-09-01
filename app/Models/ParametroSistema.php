<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParametroSistema extends Model
{
    protected $table = 'parametros_sistema';

    protected $fillable = [
        'chave',
        'valor',
        'descricao',
        'tipo',
        'editavel_por',
    ];

    /**
     * Get typed parameter value.
     */
    public function getValorTipadoAttribute()
    {
        if ($this->tipo === 'booleano' || $this->tipo === 'boolean') {
            return filter_var($this->valor, FILTER_VALIDATE_BOOLEAN);
        }
        if ($this->tipo === 'numero' || $this->tipo === 'number') {
            return (float) $this->valor;
        }
        return $this->valor;
    }

    /**
     * Helper to get system parameter value by key.
     */
    public static function getVal(string $chave, $default = null)
    {
        $param = self::where('chave', $chave)->first();
        return $param ? $param->valor_tipado : $default;
    }
}
