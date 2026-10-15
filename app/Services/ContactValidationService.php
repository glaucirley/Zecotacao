<?php

namespace App\Services;

use App\Models\User;
use App\Models\ClienteContato;

class ContactValidationService
{
    /**
     * Normaliza um telefone para o formato E.164 (55 + DDD + Número).
     */
    public function normalize(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        // Remove zeros à esquerda (ex: 034 -> 34)
        $digits = ltrim($digits, '0');

        // Se não tiver o DDI 55 do Brasil, adiciona
        if (!str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }

        return $digits;
    }

    /**
     * Valida se o número é um celular válido com WhatsApp,
     * rejeitando fixos e números de colaboradores internos.
     *
     * @param string $phone
     * @param int|null $currentParceiroId
     * @return array{valid: bool, normalized: ?string, error: ?string, warning: ?string}
     */
    public function validate(string $phone, ?int $currentParceiroId = null): array
    {
        $raw = trim($phone);
        if (empty($raw)) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'Informe o número de telefone do cliente.',
                'warning' => null,
            ];
        }

        $normalized = $this->normalize($raw);
        $nationalDigits = substr($normalized, 2); // remove o 55

        // Se tiver 10 dígitos (DDD + 8 dígitos) -> Telefone Fixo
        if (strlen($nationalDigits) === 10) {
            return [
                'valid' => false,
                'normalized' => $normalized,
                'error' => 'Número fixo não possui WhatsApp. Informe um celular com 9 dígitos.',
                'warning' => null,
            ];
        }

        // Celular brasileiro no formato E.164 deve ter exatamente 13 dígitos (55 + DDD 2 + 9 dígitos)
        if (strlen($normalized) !== 13) {
            return [
                'valid' => false,
                'normalized' => $normalized,
                'error' => 'Formato de celular inválido. Informe o DDD e o número completo (ex: 34 99123-4567).',
                'warning' => null,
            ];
        }

        // Validação de DDD (11 a 99)
        $ddd = (int)substr($nationalDigits, 0, 2);
        if ($ddd < 11 || $ddd > 99) {
            return [
                'valid' => false,
                'normalized' => $normalized,
                'error' => "DDD ({$ddd}) inválido. Informe um DDD brasileiro válido.",
                'warning' => null,
            ];
        }

        // O primeiro dígito após o DDD deve ser obrigatoriamente 9
        $firstDigit = substr($nationalDigits, 2, 1);
        if ($firstDigit !== '9') {
            return [
                'valid' => false,
                'normalized' => $normalized,
                'error' => 'Celulares com WhatsApp devem iniciar com o dígito 9 após o DDD.',
                'warning' => null,
            ];
        }

        // Rejeitar número de qualquer usuário interno do sistema (representante, gestor, diretor, admin)
        $isInternalUser = User::all()->contains(function ($user) use ($nationalDigits, $normalized) {
            if (!$user->telefone) return false;
            $userDigits = preg_replace('/\D/', '', $user->telefone);
            $userDigits = ltrim($userDigits, '0');
            if (str_starts_with($userDigits, '55')) {
                $userDigits = substr($userDigits, 2);
            }
            return $userDigits === $nationalDigits;
        });

        if ($isInternalUser) {
            return [
                'valid' => false,
                'normalized' => $normalized,
                'error' => 'Este número pertence a um usuário interno da equipe comercial e não pode ser cadastrado como contato do cliente.',
                'warning' => null,
            ];
        }

        // Alerta informativo (sem bloquear) se já estiver cadastrado em outro cliente
        $warning = null;
        if ($currentParceiroId) {
            $otherContact = ClienteContato::where('telefone', $normalized)
                ->where('parceiro_id', '!=', $currentParceiroId)
                ->with('parceiro')
                ->first();

            if ($otherContact && $otherContact->parceiro) {
                $warning = "Aviso: Este número já está registrado no cliente {$otherContact->parceiro->razao_social} (Cód. {$otherContact->parceiro->codigo_sankhya}).";
            }
        }

        return [
            'valid' => true,
            'normalized' => $normalized,
            'error' => null,
            'warning' => $warning,
        ];
    }
}
