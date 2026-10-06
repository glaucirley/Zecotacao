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
            }
        }

        $limit = $request->filled('limit') ? min((int)$request->input('limit'), 100) : 20;

        $products = $query->orderBy('descricao')->take($limit)->get();

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
                'message' => 'Product created successfully.',
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to create product. ' . $e->getMessage()
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
                'message' => 'Product updated successfully.',
                'data' => $product
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to update product. ' . $e->getMessage()
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
                'message' => 'Product deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to delete product. It might be used in existing quotations. Try deactivating it instead.'
            ], 500);
        }
    }
}
