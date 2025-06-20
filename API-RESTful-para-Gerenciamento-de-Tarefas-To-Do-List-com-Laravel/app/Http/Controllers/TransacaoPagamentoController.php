<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransacaoPagamento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

/**
 * @group Transações de Pagamento
 *
 * APIs para gerenciar transações de pagamento associadas a uma loja.
 */
class TransacaoPagamentoController extends Controller
{
    /**
     * Listar transações de pagamento
     *
     * Retorna uma lista de todas as transações de pagamento associadas à loja do usuário autenticado.
     *
     * @authenticated
     * @response 200 [
     *   {
     *     "id": 1,
     *     "cliente_id": 1,
     *     "metodo_pagamento": 1,
     *     "pedido_id": 1,
     *     "valor_total": 150.00,
     *     "loja_id": 1,
     *     "created_at": "2025-05-28T16:04:00.000000Z",
     *     "updated_at": "2025-05-28T16:04:00.000000Z"
     *   }
     * ]
     * @responseError 500 {
     *   "error": "Erro ao listar as transações de pagamento."
     * }
     */
    public function index(): JsonResponse
    {
        try {
            // Obter o ID da loja do usuário logado
            $loja_id = Auth::user()->loja_id;

            // Buscar todas as transações de pagamento relacionadas à loja logada
            $transacoes = TransacaoPagamento::where('loja_id', $loja_id)->get();

            return response()->json($transacoes, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao listar as transações de pagamento.'], 500);
        }
    }

    /**
     * Criar uma nova transação de pagamento
     *
     * Cria uma nova transação de pagamento associada à loja do usuário autenticado.
     *
     * @authenticated
     * @bodyParam cliente_id integer O ID do cliente associado à transação. Exemplo: 1
     * @bodyParam metodo_pagamento integer required O ID do método de pagamento. Exemplo: 1
     * @bodyParam pedido_id integer required O ID do pedido associado. Exemplo: 1
     * @bodyParam valor_total number required O valor total da transação. Exemplo: 150.00
     * @response 201 {
     *   "message": "Transação de pagamento cadastrada com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "cliente_id": 1,
     *     "metodo_pagamento": 1,
     *     "pedido_id": 1,
     *     "valor_total": 150.00,
     *     "loja_id": 1,
     *     "created_at": "2025-05-28T16:04:00.000000Z",
     *     "updated_at": "2025-05-28T16:04:00.000000Z"
     *   }
     * }
     * @responseError 422 {
     *   "error": {
     *     "metodo_pagamento": ["O campo metodo_pagamento é obrigatório."],
     *     "pedido_id": ["O campo pedido_id é obrigatório."],
     *     "valor_total": ["O campo valor_total é obrigatório."]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao cadastrar a transação de pagamento."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'cliente_id' => 'nullable|exists:users,id',
                'metodo_pagamento' => 'required|exists:forma_pagamentos,id',
                'pedido_id' => 'required|exists:pedidos,id',
                'valor_total' => 'required|numeric|min:0',
            ]);

            // Criação da transação de pagamento
            $transacao = TransacaoPagamento::create([
                'cliente_id' => $request->cliente_id,
                'metodo_pagamento' => $request->metodo_pagamento,
                'pedido_id' => $request->pedido_id,
                'valor_total' => $request->valor_total,
                'loja_id' => Auth::user()->loja_id,  // Associar a loja logada
            ]);

            return response()->json([
                'message' => 'Transação de pagamento cadastrada com sucesso!',
                'data' => $transacao
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao cadastrar a transação de pagamento.'], 500);
        }
    }

    /**
     * Exibir uma transação de pagamento
     *
     * Retorna os detalhes de uma transação de pagamento específica com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id string required O ID da transação de pagamento. Exemplo: 1
     * @response 200 {
     *   "id": 1,
     *   "cliente_id": 1,
     *   "metodo_pagamento": 1,
     *   "pedido_id": 1,
     *   "valor_total": 150.00,
     *   "loja_id": 1,
     *   "created_at": "2025-05-28T16:04:00.000000Z",
     *   "updated_at": "2025-05-28T16:04:00.000000Z"
     * }
     * @responseError 404 {
     *   "error": "Transação não encontrada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao exibir a transação de pagamento."
     * }
     */
    public function show($id): JsonResponse
    {
        try {
            $transacao = TransacaoPagamento::where('id', $id)->where('loja_id', Auth::user()->loja_id)->first();

            if (!$transacao) {
                return response()->json(['error' => 'Transação não encontrada'], 404);
            }

            return response()->json($transacao, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao exibir a transação de pagamento.'], 500);
        }
    }

    /**
     * Atualizar uma transação de pagamento
     *
     * Atualiza os dados de uma transação de pagamento existente com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id string required O ID da transação de pagamento. Exemplo: 1
     * @bodyParam cliente_id integer O ID do cliente associado à transação. Exemplo: 1
     * @bodyParam metodo_pagamento integer required O ID do método de pagamento. Exemplo: 1
     * @bodyParam pedido_id integer required O ID do pedido associado. Exemplo: 1
     * @bodyParam valor_total number required O valor total da transação. Exemplo: 150.00
     * @response 200 {
     *   "message": "Transação de pagamento atualizada com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "cliente_id": 1,
     *     "metodo_pagamento": 1,
     *     "pedido_id": 1,
     *     "valor_total": 150.00,
     *     "loja_id": 1,
     *     "created_at": "2025-05-28T16:04:00.000000Z",
     *     "updated_at": "2025-05-28T16:04:00.000000Z"
     *   }
     * }
     * @responseError 404 {
     *   "error": "Transação não encontrada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "metodo_pagamento": ["O campo metodo_pagamento é obrigatório."],
     *     "pedido_id": ["O campo pedido_id é obrigatório."],
     *     "valor_total": ["O campo valor_total é obrigatório."]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar a transação de pagamento."
     * }
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'cliente_id' => 'nullable|exists:users,id',
                'metodo_pagamento' => 'required|exists:forma_pagamentos,id',
                'pedido_id' => 'required|exists:pedidos,id',
                'valor_total' => 'required|numeric|min:0',
            ]);

            $transacao = TransacaoPagamento::where('id', $id)->where('loja_id', Auth::user()->loja_id)->first();

            if (!$transacao) {
                return response()->json(['error' => 'Transação não encontrada'], 404);
            }

            // Atualiza a transação de pagamento
            $transacao->update($request->all());

            return response()->json([
                'message' => 'Transação de pagamento atualizada com sucesso!',
                'data' => $transacao
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar a transação de pagamento.'], 500);
        }
    }

    /**
     * Remover uma transação de pagamento
     *
     * Remove uma transação de pagamento com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id string required O ID da transação de pagamento. Exemplo: 1
     * @response 200 {
     *   "message": "Transação de pagamento removida com sucesso"
     * }
     * @responseError 404 {
     *   "error": "Transação não encontrada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar a transação de pagamento."
     * }
     */
    public function destroy($id): JsonResponse
    {
        try {
            $transacao = TransacaoPagamento::where('id', $id)->where('loja_id', Auth::user()->loja_id)->first();

            if (!$transacao) {
                return response()->json(['error' => 'Transação não encontrada'], 404);
            }

            // Deleta a transação de pagamento
            $transacao->delete();

            return response()->json(['message' => 'Transação de pagamento removida com sucesso'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao deletar a transação de pagamento.'], 500);
        }
    }
}
