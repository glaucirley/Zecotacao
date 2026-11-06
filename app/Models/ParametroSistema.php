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
     * Cache estático em memória para evitar dezenas de queries repetidas na mesma requisição.
     */
    protected static array $runtimeCache = [];

    protected static function booted()
    {
        static::saved(function () {
            self::flushRuntimeCache();
        });
        static::deleted(function () {
            self::flushRuntimeCache();
        });
    }

    /**
     * Limpa o cache estático em memória.
     */
    public static function flushRuntimeCache(): void
    {
        self::$runtimeCache = [];
    }

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
     * Helper to get system parameter value by key with instant memory cache.
     */
    public static function getVal(string $chave, $default = null)
    {
        if (array_key_exists($chave, self::$runtimeCache)) {
            return self::$runtimeCache[$chave];
        }

        try {
            $param = self::where('chave', $chave)->first();
            $val = $param ? $param->valor_tipado : $default;
            self::$runtimeCache[$chave] = $val;
            return $val;
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
