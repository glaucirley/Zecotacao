<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PartnerController extends Controller
{
    /**
     * List partners/clients. Restricted to Administrators. Limited to 20 items by default.
     * High-performance query with index scans, terms filtering and query cache.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = trim((string)$request->input('search', ''));
        $status = $request->input('status');
        $limit = $request->filled('limit') ? min((int)$request->input('limit'), 100) : 20;

        $restringir = filter_var(\App\Models\ParametroSistema::getVal('RESTRINGIR_CLIENTES_POR_REPRESENTANTE', true), FILTER_VALIDATE_BOOLEAN);
        $userScope = $user ? ($user->papel . '_' . ($user->codigo_sankhya ?: $user->id) . '_' . ($restringir ? '1' : '0')) : 'all';

        $cacheKey = 'partners_search_v7_' . md5($userScope . '_' . mb_strtolower($search) . '_' . ($status ?? '') . '_' . $limit);

        $partners = Cache::remember($cacheKey, 60, function () use ($search, $status, $limit, $user, $restringir) {
            $query = Parceiro::query()->select([
                'id', 'codigo_sankhya', 'razao_social', 'nome_fantasia',
                'cnpj', 'cidade', 'uf', 'bairro', 'ativo',
                'codigo_regiao', 'codigo_tabela_preco', 'carteira_livre',
                'vendedor_1_codigo', 'vendedor_2_codigo', 'vendedor_3_codigo', 'vendedor_4_codigo', 'vendedor_5_codigo', 'vendedor_6_codigo'
            ]);

            // Regra de Proteção de Carteira com AD_CARTLIVRE
            if ($restringir && $user) {
                $isSuperRole = $user->isAdministrador() || $user->isDiretor() || $user->isFaturamento() || $user->isSuporte() || ($user->papel === 'suporte');

                if (!$isSuperRole) {
                    if ($user->isGestor()) {
                        // Gestor: vê Carteira Livre + clientes da sua equipe
                        $memberCodes = $user->equipe ? $user->equipe->membros()->pluck('codigo_sankhya')->filter()->all() : [];
                        $teamCodes = array_values(array_unique(array_filter(array_merge([$user->codigo_sankhya], $memberCodes))));

                        $query->where(function ($q) use ($teamCodes) {
                            $q->where('carteira_livre', true);
                            if (!empty($teamCodes)) {
                                $q->orWhereIn('vendedor_1_codigo', $teamCodes)
                                  ->orWhereIn('vendedor_2_codigo', $teamCodes)
                                  ->orWhereIn('vendedor_3_codigo', $teamCodes)
                                  ->orWhereIn('vendedor_4_codigo', $teamCodes)
                                  ->orWhereIn('vendedor_5_codigo', $teamCodes)
                                  ->orWhereIn('vendedor_6_codigo', $teamCodes);
                            }
                        });
                    } elseif ($user->isRepresentante() || in_array($user->papel, ['representante', 'vendedor'])) {
                        // Vendedor (Externo ou Televendas/Interno): vê apenas sua carteira + Carteira Livre
                        $codVend = trim((string)($user->codigo_sankhya ?? ''));

                        $query->where(function ($q) use ($codVend) {
                            $q->where('carteira_livre', true);
                            if ($codVend !== '') {
                                $q->orWhere('vendedor_1_codigo', $codVend)
                                  ->orWhere('vendedor_2_codigo', $codVend)
                                  ->orWhere('vendedor_3_codigo', $codVend)
                                  ->orWhere('vendedor_4_codigo', $codVend)
                                  ->orWhere('vendedor_5_codigo', $codVend)
                                  ->orWhere('vendedor_6_codigo', $codVend);

                                if (is_numeric($codVend)) {
                                    $intCod = (string)((int)$codVend);
                                    $q->orWhere('vendedor_1_codigo', $intCod)
                                      ->orWhere('vendedor_2_codigo', $intCod)
                                      ->orWhere('vendedor_3_codigo', $intCod)
                                      ->orWhere('vendedor_4_codigo', $intCod)
                                      ->orWhere('vendedor_5_codigo', $intCod)
                                      ->orWhere('vendedor_6_codigo', $intCod);
                                }
                            }
                        });
                    }
                }
            }

            if ($search !== '') {
                $cleanDigits = preg_replace('/[^0-9]/', '', $search);
                $terms = array_filter(explode(' ', $search));
                $significantTerms = array_values(array_filter($terms, fn($t) => mb_strlen($t) >= 2));
                if (empty($significantTerms)) {
                    $significantTerms = $terms;
                }

                $query->where(function ($q) use ($significantTerms, $cleanDigits, $search) {
                    if (strlen($cleanDigits) >= 3) {
                        $q->where('cnpj', 'like', "%{$cleanDigits}%")
                          ->orWhere('codigo_sankhya', 'like', "%{$search}%");
                    }

                    $q->orWhere(function ($allTermsQ) use ($significantTerms) {
                        foreach ($significantTerms as $term) {
                            $allTermsQ->where(function ($subQ) use ($term) {
                                $subQ->where('razao_social', 'like', "%{$term}%")
                                     ->orWhere('nome_fantasia', 'like', "%{$term}%")
                                     ->orWhere('codigo_sankhya', 'like', "%{$term}%");
                            });
                        }
                    });
                });

                $safeSearch = addslashes($search);
                $query->orderByRaw("
                    CASE 
                        WHEN codigo_sankhya = '{$safeSearch}' THEN 1
                        WHEN razao_social LIKE '{$safeSearch}%' OR nome_fantasia LIKE '{$safeSearch}%' THEN 2
                        ELSE 3
                    END ASC,
                    razao_social ASC
                ");
            } else {
                $query->orderBy('razao_social');
            }

            if ($status === 'ativo') {
                $query->where('ativo', true);
            } elseif ($status === 'inativo') {
                $query->where('ativo', false);
            }

            $results = $query->take($limit)->get();

            // Se for busca pontual e algum parceiro estiver sem tabela de preços,
            // tenta obter TSIREG.CODTAB do Sankhya sob demanda
            if ($search !== '' && $results->count() <= 10) {
                $sankhyaDb = app(\App\Services\SankhyaDatabaseService::class);
                foreach ($results as $p) {
                    if (empty($p->codigo_tabela_preco) && !empty($p->codigo_sankhya)) {
                        try {
                            $codTab = $sankhyaDb->fetchRegionPriceTableForPartner($p->codigo_sankhya);
                            if (!empty($codTab)) {
                                $p->codigo_tabela_preco = $codTab;
                                Parceiro::where('id', $p->id)->update(['codigo_tabela_preco' => $codTab]);
                            } else {
                                $refreshed = $sankhyaDb->syncPartnerByCode($p->codigo_sankhya);
                                if ($refreshed && !empty($refreshed->codigo_tabela_preco)) {
                                    $p->codigo_tabela_preco = $refreshed->codigo_tabela_preco;
                                    $p->codigo_regiao = $refreshed->codigo_regiao;
                                }
                            }
                        } catch (\Throwable $t) {
                            // Silently continue if Sankhya unreachable
                        }
                    }
                }
            }

            return $results;
        });

        return response()->json([
            'success' => true,
            'data' => $partners
        ]);
    }

    /**
     * Store a new partner.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdministrador() && !$user->isSuporte()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'codigo_sankhya' => 'required|string|max:100|unique:parceiros,codigo_sankhya',
            'razao_social' => 'required|string|max:255',
            'nome_fantasia' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:50|unique:parceiros,cnpj',
            'telefone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'endereco' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:100',
            'uf' => 'nullable|string|max:2',
            'cep' => 'nullable|string|max:20',
            'ativo' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            $partner = Parceiro::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Cliente criado com sucesso.',
                'data' => $partner
            ], 201);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao criar cliente: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Erro interno do servidor',
                'code' => 'PARTNER_CREATE_ERROR',
                'message' => config('app.debug') ? ('Falha ao criar cliente: ' . $e->getMessage()) : 'Ocorreu um erro ao criar o cliente. Os detalhes foram registrados nos logs do servidor.'
            ], 500);
        }
    }

    /**
     * Update an existing partner.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isAdministrador() && !$user->isSuporte()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $partner = Parceiro::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'codigo_sankhya' => ['required', 'string', 'max:100', Rule::unique('parceiros', 'codigo_sankhya')->ignore($partner->id)],
            'razao_social' => 'required|string|max:255',
            'nome_fantasia' => 'nullable|string|max:255',
            'cnpj' => ['nullable', 'string', 'max:50', Rule::unique('parceiros', 'cnpj')->ignore($partner->id)],
            'telefone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'endereco' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:100',
            'uf' => 'nullable|string|max:2',
            'cep' => 'nullable|string|max:20',
            'ativo' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            $partner->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Cliente atualizado com sucesso.',
                'data' => $partner
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao atualizar cliente ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Erro interno do servidor',
                'code' => 'PARTNER_UPDATE_ERROR',
                'message' => config('app.debug') ? ('Falha ao atualizar cliente: ' . $e->getMessage()) : 'Ocorreu um erro ao atualizar o cliente. Os detalhes foram registrados nos logs do servidor.'
            ], 500);
        }
    }

    /**
     * Delete an existing partner.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $partner = Parceiro::findOrFail($id);

        try {
            $partner->delete();
            return response()->json([
                'success' => true,
                'message' => 'Cliente excluído com sucesso.'
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao excluir cliente ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Erro interno do servidor',
                'code' => 'PARTNER_DELETE_ERROR',
                'message' => 'Falha ao excluir cliente. Pode haver cotações vinculadas. Tente desativá-lo.'
            ], 500);
        }
    }

    /**
     * Import partners from an uploaded CSV file.
     */
    public function importFile(Request $request, \App\Services\PartnerImportService $importService)
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'arquivo' => 'required|file|max:51200', // max 50MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Arquivo inválido. Selecione um arquivo CSV ou TXT de até 50MB.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $file = $request->file('arquivo');
        $path = $file->getRealPath();

        try {
            $result = $importService->importFromCsv($path);

            return response()->json([
                'success'     => true,
                'message'     => "Importação concluída: {$result['criados']} novos clientes cadastrados e {$result['atualizados']} atualizados de um total de {$result['total']} registros.",
                'total'       => $result['total'],
                'criados'     => $result['criados'],
                'atualizados' => $result['atualizados'],
                'erros'       => $result['erros'],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao importar CSV de clientes: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor',
                'code' => 'PARTNER_IMPORT_ERROR',
                'message' => config('app.debug') ? ('Erro ao processar o arquivo: ' . $e->getMessage()) : 'Erro ao processar o arquivo de clientes. Os detalhes foram registrados nos logs do servidor.'
            ], 500);
        }
    }

    /**
     * Sincroniza um cliente específico com o Sankhya sob demanda.
     */
    public function syncFromSankhya($id)
    {
        $partner = Parceiro::findOrFail($id);
        if (empty($partner->codigo_sankhya)) {
            return response()->json([
                'success' => false,
                'error' => 'Parceiro sem código Sankhya cadastrado.'
            ], 422);
        }

        try {
            $sankhyaDb = app(\App\Services\SankhyaDatabaseService::class);

            // 1. Consulta direta e prioritária na TSIREG.CODTAB
            $codTab = $sankhyaDb->fetchRegionPriceTableForPartner($partner->codigo_sankhya);
            if (!empty($codTab)) {
                $partner->update(['codigo_tabela_preco' => $codTab]);
                $partner->refresh();
                return response()->json([
                    'success' => true,
                    'message' => "Tabela de preços da região obtida da TSIREG com sucesso (#{$codTab}).",
                    'data' => $partner
                ]);
            }

            // 2. Fallback de sincronização completa do parceiro
            $synced = $sankhyaDb->syncPartnerByCode($partner->codigo_sankhya);
            if ($synced && !empty($synced->codigo_tabela_preco)) {
                return response()->json([
                    'success' => true,
                    'message' => "Dados e tabela de preços da região atualizados com sucesso (#{$synced->codigo_tabela_preco}).",
                    'data' => $synced
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => "A região do cliente (Código Sankhya: {$partner->codigo_sankhya}) não possui tabela de preços vinculada no campo CODTAB da tabela TSIREG do Sankhya.",
                'data' => $partner
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Falha ao sincronizar com Sankhya: ' . $e->getMessage(),
                'data' => $partner
            ], 500);
        }
    }
}

