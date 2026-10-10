<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * List products. Restricted to Administrators. Limited to 20 items by default.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden. Only administrators can manage products.'], 403);
        }

        $query = Produto::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $terms = array_filter(explode(' ', $search));
            if (!empty($terms)) {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->where(function ($subQ) use ($term) {
                            $subQ->where('codigo_sankhya', 'like', "%{$term}%")
                                 ->orWhere('descricao', 'like', "%{$term}%")
                                 ->orWhere('unidade', 'like', "%{$term}%")
                                 ->orWhere('marca', 'like', "%{$term}%")
                                 ->orWhere('ncm', 'like', "%{$term}%");
                        });
                    }
                });
            }
        }

        if ($request->filled('status')) {
            $st = $request->input('status');
            if ($st === 'ativo') {
                $query->where('ativo', true);
            } elseif ($st === 'inativo') {
                $query->where('ativo', false);
            } elseif ($st === 'problema') {
                $query->where(function($q) {
                    $q->whereDoesntHave('precos')
                      ->orWhereHas('precos', function($p) {
                          $p->where('preco_venda', '<=', 0)
                            ->orWhere('preco_venda', 100.00);
                      })
                      ->orWhere('descricao', '^')
                      ->orWhere('codigo_sankhya', '0')
                      ->orWhere('codigo_sankhya', '')
                      ->orWhereNull('codigo_sankhya')
                      ->orWhere('descricao', 'like', '%descric?o%')
                      ->orWhere('descricao', 'like', '^%')
                      ->orWhere('descricao', 'like', '<sem%');
                });
            }
        }

        $limit = $request->filled('limit') ? min((int)$request->input('limit'), 100) : 20;

        $products = $query->orderByRaw("
            CASE 
                WHEN codigo_sankhya = '0' OR codigo_sankhya = '^' THEN 2
                WHEN descricao LIKE '^%' OR descricao LIKE '<%' OR descricao = '' OR descricao IS NULL THEN 2
                ELSE 1
            END ASC
        ")->orderBy('descricao')->take($limit)->get();

        $products->transform(function ($p) {
            if ($p->descricao) {
                $p->descricao = str_ireplace(['descric?o', 'descrio'], 'descrição', $p->descricao);
            }
            return $p;
        });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Store a new product.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'codigo_sankhya' => 'required|string|max:100|unique:produtos,codigo_sankhya',
            'descricao' => 'required|string|max:255',
            'unidade' => 'required|string|max:50',
            'ativo' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            $product = Produto::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Produto criado com sucesso.',
                'data' => $product
            ], 201);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao criar produto: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Erro interno do servidor',
                'code' => 'PRODUCT_CREATE_ERROR',
                'message' => config('app.debug') ? ('Falha ao criar produto: ' . $e->getMessage()) : 'Ocorreu um erro ao criar o produto. Os detalhes foram registrados nos logs do servidor.'
            ], 500);
        }
    }

    /**
     * Update an existing product.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $product = Produto::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'codigo_sankhya' => ['required', 'string', 'max:100', Rule::unique('produtos', 'codigo_sankhya')->ignore($product->id)],
            'descricao' => 'required|string|max:255',
            'unidade' => 'required|string|max:50',
            'ativo' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            $product->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Produto atualizado com sucesso.',
                'data' => $product
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao atualizar produto ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Erro interno do servidor',
                'code' => 'PRODUCT_UPDATE_ERROR',
                'message' => config('app.debug') ? ('Falha ao atualizar produto: ' . $e->getMessage()) : 'Ocorreu um erro ao atualizar o produto. Os detalhes foram registrados nos logs do servidor.'
            ], 500);
        }
    }

    /**
     * Delete an existing product.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $product = Produto::findOrFail($id);

        try {
            $product->delete();
            return response()->json([
                'success' => true,
                'message' => 'Produto excluído com sucesso.'
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao excluir produto ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Erro interno do servidor',
                'code' => 'PRODUCT_DELETE_ERROR',
                'message' => 'Falha ao excluir produto. Ele pode estar em uso em cotações. Tente desativá-lo.'
            ], 500);
        }
    }
}
