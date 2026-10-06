<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nome',
        'papel',
        'equipe_id',
        'email',
        'senha_hash',
        'telefone',
        'codigo_sankhya',
        'limite_desconto_percentual',
        'ativo',
        'permissoes_dashboard',
        'acesso_chat',
    ];

    protected $hidden = [
        'senha_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'senha_hash' => 'hashed',
            'ativo' => 'boolean',
            'limite_desconto_percentual' => 'decimal:2',
            'permissoes_dashboard' => 'array',
            'acesso_chat' => 'boolean',
        ];
    }

    /**
     * Override password field for Laravel Auth.
     */
    public function getAuthPassword()
    {
        return $this->senha_hash;
    }

    /**
     * Relationship with the Team the user belongs to.
     */
    public function equipe()
    {
        return $this->belongsTo(Equipe::class, 'equipe_id');
    }

    /**
     * Teams managed by this user (if they are a gestor).
     */
    public function equipesGerenciadas()
    {
        return $this->hasMany(Equipe::class, 'gestor_id');
    }

    /**
     * Quotes created by this representative.
     */
    public function cotacoes()
    {
        return $this->hasMany(Cotacao::class, 'representante_id');
    }

    /**
     * Helper check roles.
     */
    public function isRepresentante(): bool
    {
        return $this->papel === 'representante';
    }

    public function isGestor(): bool
    {
        return $this->papel === 'gestor';
    }

    public function isDiretor(): bool
    {
        return $this->papel === 'diretor';
    }

    public function isFaturamento(): bool
    {
        return $this->papel === 'faturamento';
    }

    public function isAdministrador(): bool
    {
        return $this->papel === 'administrador';
    }

    /**
     * Check if user is authorized to view or edit a specific quotation.
     * - Administrador & Diretor: see all quotations.
     * - Faturamento: see quotations.
     * - Gestor: only quotations from representatives in their team(s) or created by themselves.
     * - Representante / others: only quotations where representante_id == user id.
     */
    public function canAccessQuote(\App\Models\Cotacao $quote): bool
    {
        if ($this->isAdministrador() || $this->isDiretor()) {
            return true;
        }

        if ($this->isFaturamento()) {
            return true;
        }

        if ($this->isGestor()) {
            if ((int) $quote->representante_id === (int) $this->id) {
                return true;
            }

            $teamIds = $this->equipesGerenciadas()->pluck('id')->toArray();
            if ($this->equipe_id && !in_array($this->equipe_id, $teamIds)) {
                $teamIds[] = $this->equipe_id;
            }

            if (!empty($teamIds)) {
                $rep = $quote->representante ?: \App\Models\User::find($quote->representante_id);
                if ($rep && $rep->equipe_id && in_array($rep->equipe_id, $teamIds)) {
                    return true;
                }
            }

            return false;
        }

        if (!$quote->representante_id) {
            return false;
        }

        return (int) $quote->representante_id === (int) $this->id;
    }

    /**
     * Check if user has access to WhatsApp chat repository.
     */
    public function hasChatAccess(): bool
    {
        if ($this->acesso_chat) {
            return true;
        }

        return $this->isAdministrador() || $this->isDiretor() || $this->isGestor();
    }

    /**
     * Check if user has granular dashboard widget permission.
     */
    public function hasDashPermission(string $perm): bool
    {
        $perms = $this->permissoes_dashboard;

        // If defined, return the boolean value
        if (is_array($perms) && isset($perms[$perm])) {
            return (bool)$perms[$perm];
        }

        // Fallback to role-based defaults
        switch ($this->papel) {
            case 'administrador':
            case 'diretor':
                return true;

            case 'gestor':
                return in_array($perm, ['ver_kpis', 'ver_evolucao_temporal', 'ver_status_dist', 'ver_ranking_vendedores', 'ver_top_clientes']);

            case 'faturamento':
                return in_array($perm, ['ver_kpis', 'ver_status_dist']);

            case 'representante':
                return in_array($perm, ['ver_evolucao_temporal']);

            default:
                return false;
        }
    }
}
