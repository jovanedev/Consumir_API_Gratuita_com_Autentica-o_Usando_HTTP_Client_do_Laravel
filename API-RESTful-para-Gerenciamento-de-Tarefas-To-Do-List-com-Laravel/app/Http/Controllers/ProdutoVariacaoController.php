<?php

namespace App\Http\Controllers;

use App\Models\ProdutoVariacao;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Variações de Produto
 *
 * APIs para gerenciar variações de produtos de uma loja.
 */
class ProdutoVariacaoController extends Controller
{
    /**
     * Listar variações de produtos
     *
     * Retorna todas as variações de produtos da loja autenticada ou filtra por um produto específico.
     *
     * @authenticated
     * @queryParam produto_id integer Opcional. ID do produto para filtrar as variações. Exemplo: 1
     * @response 200 {
     *     "data": [
     *         {
     *             "id": 1,
     *             "produto_id": 1,
     *             "tipo_variacao": "Tamanho",
     *             "valor_variacao": "M",
     *             "estoque": 50,
     *             "preco_adicional": 10.00,
     *             "created_at": "2025-05-28T16:01:00.000000Z",
     *             "updated_at": "2025-05-28T16:01:00.000000Z"
     *         }
     *     ]
     * }
     * @response 404 {
     *     "error": "Produto não encontrado ou não pertence à sua loja."
     * }
     * @response 500 {
     *     "error": "Erro ao listar as variações."
     * }
     */
    public function index(Request $request)
    {
        try {
            $loja_id = Auth::user()->loja_id;

            if ($request->has('produto_id')) {
                $produto = Produto::where('id', $request->produto_id)
                    ->where('loja_id', $loja_id)
                    ->first();

                if (!$produto) {
                    return response()->json(['error' => 'Produto não encontrado ou não pertence à sua loja.'], 404);
                }

                $variacoes = ProdutoVariacao::where('produto_id', $produto->id)->get();
            } else {
                $variacoes = ProdutoVariacao::whereHas('produto', function ($query) use ($loja_id) {
                    $query->where('loja_id', $loja_id);
                })->get();
            }

            return response()->json($variacoes, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao listar as variações.'], 500);
        }
    }

    /**
     * Cadastrar uma variação de produto
     *
     * Cria uma nova variação para um produto pertencente à loja autenticada.
     *
     * @authenticated
     * @bodyParam produto_id integer required ID do produto ao qual a variação pertence. Exemplo: 1
     * @bodyParam tipo_variacao string required Tipo da variação (ex.: Tamanho, Cor). Exemplo: "Tamanho"
     * @bodyParam valor_variacao string required Valor da variação (ex.: M, Azul). Exemplo: "M"
     * @bodyParam estoque integer required Quantidade em estoque. Exemplo: 50
     * @bodyParam preco_adicional float required Preço adicional da variação. Exemplo: 10.00
     * @response 201 {
     *     "message": "Variação cadastrada com sucesso!",
     *     "data": {
     *         "id": 1,
     *         "produto_id": 1,
     *         "tipo_variacao": "Tamanho",
     *         "valor_variacao": "M",
     *         "estoque": 50,
     *         "preco_adicional": 10.00,
     *         "created_at": "2025-05-28T16:01:00.000000Z",
     *         "updated_at": "2025-05-28T16:01:00.000000Z"
     *     }
     * }
     * @response 403 {
     *     "error": "Este produto não pertence à sua loja."
     * }
     * @response 422 {
     *     "error": "Os dados fornecidos são inválidos.",
     *     "errors": {
     *         "produto_id": ["O campo produto_id é obrigatório."],
     *         "tipo_variacao": ["O campo tipo_variacao é obrigatório."]
     *     }
     * }
     */
    public function store(Request $request)
    {
        $request->validate([
            'produto_id' => 'required|exists:produtos,id',
            'tipo_variacao' => 'required|string|max:255',
            'valor_variacao' => 'required|string|max:255',
            'estoque' => 'required|integer|min:0',
            'preco_adicional' => 'required|numeric|min:0',
        ]);

        $produto = Produto::find($request->produto_id);
        if ($produto->loja_id != Auth::user()->loja_id) {
            return response()->json(['error' => 'Este produto não pertence à sua loja.'], 403);
        }

        $variacao = ProdutoVariacao::create($request->all());

        return response()->json([
            'message' => 'Variação cadastrada com sucesso!',
            'data' => $variacao
        ], 201);
    }

    /**
     * Atualizar uma variação de produto
     *
     * Atualiza os dados de uma variação específica pertencente à loja autenticada.
     *
     * @authenticated
     * @urlParam id integer required ID da variação a ser atualizada. Exemplo: 1
     * @bodyParam tipo_variacao string Opcional. Tipo da variação (ex.: Tamanho, Cor). Exemplo: "Tamanho"
     * @bodyParam valor_variacao string Opcional. Valor da variação (ex.: M, Azul). Exemplo: "M"
     * @bodyParam estoque integer Opcional. Quantidade em estoque. Exemplo: 50
     * @bodyParam preco_adicional float Opcional. Preço adicional da variação. Exemplo: 10.00
     * @response 200 {
     *     "message": "Variação atualizada com sucesso!",
     *     "data": {
     *         "id": 1,
     *         "produto_id": 1,
     *         "tipo_variacao": "Tamanho",
     *         "valor_variacao": "M",
     *         "estoque": 50,
     *         "preco_adicional": 10.00,
     *         "created_at": "2025-05-28T16:01:00.000000Z",
     *         "updated_at": "2025-05-28T16:01:00.000000Z"
     *     }
     * }
     * @response 403 {
     *     "error": "Esta variação não pertence à sua loja."
     * }
     * @response 422 {
     *     "error": "Os dados fornecidos são inválidos.",
     *     "errors": {
     *         "estoque": ["O campo estoque deve ser um inteiro maior ou igual a 0."]
     *     }
     * }
     */
    public function update(Request $request, $id)
    {
        $variacao = ProdutoVariacao::find($id);

        if ($variacao->produto->loja_id != Auth::user()->loja_id) {
            return response()->json(['error' => 'Esta variação não pertence à sua loja.'], 403);
        }

        $request->validate([
            'tipo_variacao' => 'string|max:255',
            'valor_variacao' => 'string|max:255',
            'estoque' => 'integer|min:0',
            'preco_adicional' => 'numeric|min:0',
        ]);

        $variacao->update($request->all());

        return response()->json([
            'message' => 'Variação atualizada com sucesso!',
            'data' => $variacao
        ]);
    }

    /**
     * Exibir uma variação específica
     *
     * Retorna os detalhes de uma variação de produto específica pertencente à loja autenticada.
     *
     * @authenticated
     * @urlParam id integer required ID da variação a ser exibida. Exemplo: 1
     * @response 200 {
     *     "id": 1,
     *     "produto_id": 1,
     *     "tipo_variacao": "Tamanho",
     *     "valor_variacao": "M",
     *     "estoque": 50,
     *     "preco_adicional": 10.00,
     *     "created_at": "2025-05-28T16:01:00.000000Z",
     *     "updated_at": "2025-05-28T16:01:00.000000Z"
     * }
     * @response 403 {
     *     "error": "Esta variação não pertence à sua loja."
     * }
     * @response 404 {
     *     "error": "Variação não encontrada"
     * }
     */
    public function show(string $id)
    {
        $produto_variacao = ProdutoVariacao::find($id);

        if ($produto_variacao->produto->loja_id != Auth::user()->loja_id) {
            return response()->json(['error' => 'Esta variação não pertence à sua loja.'], 403);
        }

        if (!$produto_variacao) {
            return response()->json(['error' => 'Variação não encontrada'], 404);
        }

        return response()->json($produto_variacao, 200);
    }

    /**
     * Deletar uma variação de produto
     *
     * Remove uma variação de produto específica pertencente à loja autenticada.
     *
     * @authenticated
     * @urlParam id integer required ID da variação a ser deletada. Exemplo: 1
     * @response 200 {
     *     "message": "Variação de Produto removida com sucesso"
     * }
     * @response 403 {
     *     "error": "Esta variação não pertence à sua loja."
     * }
     * @response 404 {
     *     "error": "Variação de Produto não encontrada"
     * }
     */
    public function destroy($id)
    {
        $produto_variacao = ProdutoVariacao::find($id);

        if ($produto_variacao->produto->loja_id != Auth::user()->loja_id) {
            return response()->json(['error' => 'Esta variação não pertence à sua loja.'], 403);
        }

        if (!$produto_variacao) {
            return response()->json(['error' => 'Variação de Produto não encontrada'], 404);
        }

        $produto_variacao->delete();

        return response()->json(['message' => 'Variação de Produto removida com sucesso'], 200);
    }

    /**
     * Atualizar o estoque de uma variação
     *
     * Atualiza o campo estoque de uma variação de produto específica pertencente à loja autenticada.
     *
     * @authenticated
     * @urlParam id integer required ID da variação a ser atualizada. Exemplo: 1
     * @bodyParam estoque integer required Quantidade em estoque. Exemplo: 50
     * @response 200 {
     *     "message": "Estoque atualizado com sucesso.",
     *     "data": {
     *         "id": 1,
     *         "produto_id": 1,
     *         "tipo_variacao": "Tamanho",
     *         "valor_variacao": "M",
     *         "estoque": 50,
     *         "preco_adicional": 10.00,
     *         "created_at": "2025-05-28T16:01:00.000000Z",
     *         "updated_at": "2025-05-28T16:01:00.000000Z"
     *     }
     * }
     * @response 403 {
     *     "error": "Esta variação não pertence à sua loja."
     * }
     * @response 404 {
     *     "error": "Variação não encontrada."
     * }
     * @response 422 {
     *     "error": "Os dados fornecidos são inválidos.",
     *     "errors": {
     *         "estoque": ["O campo estoque é obrigatório."]
     *     }
     * }
     */
    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'estoque' => 'required|integer|min:0',
        ]);

        $variacao = ProdutoVariacao::find($id);

        if ($variacao->produto->loja_id != Auth::user()->loja_id) {
            return response()->json(['error' => 'Esta variação não pertence à sua loja.'], 403);
        }

        if (!$variacao) {
            return response()->json(['error' => 'Variação não encontrada.'], 404);
        }

        $variacao->estoque = $request->estoque;
        $variacao->save();

        return response()->json([
            'message' => 'Estoque atualizado com sucesso.',
            'data' => $variacao
        ], 200);
    }
}
