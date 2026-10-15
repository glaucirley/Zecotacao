<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteContato extends Model
{
    protected $table = 'cliente_contatos';

    protected $fillable = [
        'parceiro_id',
        'codigo_sankhya_parc',
        'nome_contato',
        'cargo',
        'telefone',
        'origem',
        'confirmado',
        'qtd_envios',
        'qtd_aberturas',
        'ultimo_envio_em',
        'ultima_abertura_em',
        'status',
        'motivo_inativacao',
        'criado_por',
    ];

    protected $casts = [
        'confirmado' => 'boolean',
        'qtd_envios' => 'integer',
        'qtd_aberturas' => 'integer',
        'ultimo_envio_em' => 'datetime',
        'ultima_abertura_em' => 'datetime',
    ];

    public function parceiro()
    {
        return $this->belongsTo(Parceiro::class, 'parceiro_id');
    }

    public function criador()
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function envios()
    {
        return $this->hasMany(CotacaoEnvio::class, 'contato_id');
    }

    /**
     * Retorna o telefone formatado no padrão visual brasileiro (XX) 9XXXX-XXXX
     */
    public function getTelefoneFormatadoAttribute(): string
    {
        $tel = preg_replace('/\D/', '', $this->telefone ?? '');
        if (str_starts_with($tel, '55')) {
            $tel = substr($tel, 2);
        }
        if (strlen($tel) === 11) {
            return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7);
        }
        if (strlen($tel) === 10) {
            return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6);
        }
        return $this->telefone ?? '';
    }
}
