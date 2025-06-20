<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group Gestão de Pedidos
 *
 * Endpoints para gerenciamento de pedidos associados a lojas.
 */
class PedidoController extends Controller
{
    /**
     * Obtém o ID da loja do usuário autenticado.
     *
     * @return int|null O ID da loja ou null se não houver loja associada.
     */
    private function getLojaId(): ?int
    {
        $user = Auth::user();
        return $user ? $user->loja_id : null;
    }

    /**
     * Valida se o usuário está autenticado e possui uma loja associada.
     *
     * @return JsonResponse|null Resposta JSON com erro 401 ou 403 se inválido, ou null se válido.
     */
    private function validateLojaId(): ?JsonResponse
    {
        if (!Auth::user()) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado.'
            ], 401);
        }
        $loja_id = $this->getLojaId();
        if (!$loja_id) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não possui loja associada.'
            ], 403);
        }
        return null;
    }

    /**
     * Lista todos os pedidos da loja autenticada.
     *
     * Retorna uma lista de pedidos associados à loja do usuário autenticado.
     *
     * @authenticated
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "loja_id": 1,
     *       "cliente_id": 1,
     *       "codigo_unico_pedido": "123e4567-e89b-12d3-a456-426614174000",
     *       "status": "pendente",
     *       "valor_total": 150.00,
     *       "valor_desconto": 10.00,
     *       "frete": 15.00,
     *       "tipo_frete": "Correios PAC",
     *       "prazo_entrega": "2025-06-05",
     *       "endereco_entrega_id": 1,
     *       "metodo_pagamento": "Cartão de Crédito",
     *       "observacoes": "Entregar após 14h",
     *       "created_at": "2025-05-28T15:48:00.000000Z",
     *       "updated_at": "2025-05-28T15:48:00.000000Z"
     *     }
     *   ]
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao listar pedidos.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function index(): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();
            $pedidos = Pedido::where('loja_id', $loja_id)->get();

            return response()->json([
                'success' => true,
                'data' => $pedidos
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao listar pedidos', ['loja_id' => $this->getLojaId(), 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria um novo pedido.
     *
     * Cria um pedido associado à loja do usuário autenticado, incluindo itens do pedido.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @return JsonResponse
     *
     * @bodyParam cliente_id int nullable ID do cliente (deve existir em users). Exemplo: 1
     * @bodyParam status string nullable Status do pedido. Exemplo: pendente
     * @bodyParam valor_total numeric nullable Valor total do pedido (mín. 0). Exemplo: 150.00
     * @bodyParam valor_desconto numeric nullable Valor do desconto (mín. 0). Exemplo: 10.00
     * @bodyParam frete numeric nullable Valor do frete (mín. 0). Exemplo: 15.00
     * @bodyParam tipo_frete string nullable Tipo de frete. Exemplo: Correios PAC
     * @bodyParam prazo_entrega date nullable Prazo de entrega. Exemplo: 2025-06-05
     * @bodyParam endereco_entrega_id int nullable ID do endereço de entrega (deve existir em enderecos). Exemplo: 1
     * @bodyParam metodo_pagamento string nullable Método de pagamento. Exemplo: Cartão de Crédito
     * @bodyParam observacoes string nullable Observações do pedido. Exemplo: Entregar após 14h
     * @bodyParam items array required Lista de itens do pedido.
     * @bodyParam items.*.produto_id int required ID do produto (deve existir em produtos). Exemplo: 1
     * @bodyParam items.*.quantidade int required Quantidade do produto (mín. 1). Exemplo: 2
     * @bodyParam items.*.preco_unitario numeric required Preço unitário do produto (mín. 0). Exemplo: 50.00
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "cliente_id": 1,
     *     "codigo_unico_pedido": "123e4567-e89b-12d3-a456-426614174000",
     *     "status": "pendente",
     *     "valor_total": 150.00,
     *     "valor_desconto": 10.00,
     *     "frete": 15.00,
     *     "tipo_frete": "Correios PAC",
     *     "prazo_entrega": "2025-06-05",
     *     "endereco_entrega_id": 1,
     *     "metodo_pagamento": "Cartão de Crédito",
     *     "observacoes": "Entregar após 14h",
     *     "created_at": "2025-05-28T15:48:00.000000Z",
     *     "updated_at": "2025-05-28T15:48:00.000000Z"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Validação falhou.",
     *   "errors": {
     *     "cliente_id": ["O campo cliente_id deve existir"],
     *     "items.0.produto_id": ["O campo produto_id é obrigatório"],
     *     "valor_total": ["O campo valor_total deve ser numérico e não negativo"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar pedido.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();
            $validator = Validator::make($request->all(), [
                'cliente_id' => 'sometimes|required|exists:users,id',
                'status' => 'sometimes|required|string|in:pendente,processando,enviado,entregue,cancelado',
                'valor_total' => 'sometimes|required|numeric|min:0',
                'valor_desconto' => 'nullable|numeric|min:0',
                'frete' => 'nullable|numeric|min:0',
                'tipo_frete' => 'nullable|string|max:255',
                'prazo_entrega' => 'nullable|date',
                'endereco_entrega_id' => 'sometimes|required|exists:enderecos,id',
                'metodo_pagamento' => 'sometimes|required|string|max:255',
                'observacoes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.produto_id' => 'required|exists:produtos,id',
                'items.*.quantidade' => 'required|integer|min:1',
                'items.*.preco_unitario' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validação falhou.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pedido = Pedido::create([
                'cliente_id' => $request->cliente_id,
                'loja_id' => $loja_id,
                'codigo_unico_pedido' => Str::uuid(),
                'status' => $request->status ?? 'pendente',
                'valor_total' => $request->valor_total,
                'valor_desconto' => $request->valor_desconto,
                'frete' => $request->frete,
                'tipo_frete' => $request->tipo_frete,
                'prazo_entrega' => $request->prazo_entrega,
                'endereco_entrega_id' => $request->endereco_entrega_id,
                'metodo_pagamento' => $request->metodo_pagamento,
                'observacoes' => $request->observacoes,
            ]);

            foreach ($request->items as $item) {
                $pedido->items()->create([
                    'produto_id' => $item['produto_id'],
                    'quantidade' => $item['quantidade'],
                    'preco_unitario' => $item['preco_unitario'],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $pedido->load('items')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao criar pedido', ['loja_id' => $this->getLojaId(), 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pedido.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe um pedido específico.
     *
     * Retorna os detalhes de um pedido com base no ID fornecido, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID do pedido.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "cliente_id": 1,
     *     "codigo_unico_pedido": "123e4567-e89b-12d3-a456-426614174000",
     *     "status": "pendente",
     *     "valor_total": 150.00,
     *     "valor_desconto": 10.00,
     *     "frete": 15.00,
     *     "tipo_frete": "Correios PAC",
     *     "prazo_entrega": "2025-06-05",
     *     "endereco_entrega_id": 1,
     *     "metodo_pagamento": "Cartão de Crédito",
     *     "observacoes": "Entregar após 14h",
     *     "created_at": "2025-05-28T15:48:00.000000Z",
     *     "updated_at": "2025-05-28T15:48:00.000000Z",
     *     "items": [
     *       {
     *         "id": 1,
     *         "pedido_id": 1,
     *         "produto_id": 1,
     *         "quantidade": 2,
     *         "preco_unitario": 50.00
     *       }
     *     ]
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para visualizar este pedido."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Pedido não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar pedido.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function show($id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();
            $pedido = Pedido::with('items')->findOrFail($id);

            if ($pedido->loja_id !== $loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para visualizar este pedido.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $pedido
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar pedido', ['loja_id' => $this->getLojaId(), 'pedido_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar pedido.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza um pedido existente.
     *
     * Atualiza os dados de um pedido específico, incluindo status, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $id O ID do pedido.
     * @return JsonResponse
     *
     * @bodyParam status string nullable Status do pedido. Exemplo: processando
     * @bodyParam valor_total numeric nullable Valor total do pedido (mín. 0). Exemplo: 160.00
     * @bodyParam valor_desconto numeric nullable Valor do desconto (mín. 0). Exemplo: 5.00
     * @bodyParam frete numeric nullable Valor do frete (mín. 0). Exemplo: 20.00
     * @bodyParam tipo_frete string nullable Tipo de frete. Exemplo: Correios SEDEX
     * @bodyParam prazo_entrega date nullable Prazo de entrega. Exemplo: 2025-06-03
     * @bodyParam endereco_entrega_id int nullable ID do endereço de entrega (deve existir em enderecos). Exemplo: 2
     * @bodyParam metodo_pagamento string nullable Método de pagamento. Exemplo: Pix
     * @bodyParam observacoes string nullable Observações do pedido. Exemplo: Entregar pela manhã
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "cliente_id": 1,
     *     "codigo_unico_pedido": "123e4567-e89b-12d3-a456-426614174000",
     *     "status": "processando",
     *     "valor_total": 160.00,
     *     "valor_desconto": 5.00,
     *     "frete": 20.00,
     *     "tipo_frete": "Correios SEDEX",
     *     "prazo_entrega": "2025-06-03",
     *     "endereco_entrega_id": 2,
     *     "metodo_pagamento": "Pix",
     *     "observacoes": "Entregar pela manhã",
     *     "created_at": "2025-05-28T15:48:00.000000Z",
     *     "updated_at": "2025-05-28T16:00:00.000000Z"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para editar este pedido."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Pedido não encontrado."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Validação falhou.",
     *   "errors": {
     *     "status": ["O campo status deve ser um dos seguintes: pendente, processando, enviado, entregue, cancelado"],
     *     "valor_total": ["O campo valor_total deve ser numérico e não negativo"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar pedido.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();
            $pedido = Pedido::findOrFail($id);

            if ($pedido->loja_id !== $loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar este pedido.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|required|string|in:pendente,processando,enviado,entregue,cancelado',
                'valor_total' => 'sometimes|required|numeric|min:0',
                'valor_desconto' => 'nullable|numeric|min:0',
                'frete' => 'nullable|numeric|min:0',
                'tipo_frete' => 'nullable|string|max:255',
                'prazo_entrega' => 'nullable|date',
                'endereco_entrega_id' => 'sometimes|required|exists:enderecos,id',
                'metodo_pagamento' => 'sometimes|required|string|max:255',
                'observacoes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validação falhou.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pedido->update($request->only([
                'status', 'valor_total', 'valor_desconto', 'frete', 'tipo_frete',
                'prazo_entrega', 'endereco_entrega_id', 'metodo_pagamento', 'observacoes'
            ]));

            return response()->json([
                'success' => true,
                'data' => $pedido
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar pedido', ['loja_id' => $this->getLojaId(), 'pedido_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar pedido.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove um pedido.
     *
     * Deleta um pedido específico, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID do pedido.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Pedido removido com sucesso."
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para remover este pedido."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Pedido não encontrado."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Não é possível remover o pedido devido a dependências.",
     *   "error": "O pedido possui itens associados."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover pedido.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function destroy($id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();
            $pedido = Pedido::findOrFail($id);

            if ($pedido->loja_id !== $loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para remover este pedido.'
                ], 403);
            }

            if ($pedido->items()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível remover o pedido devido a dependências.',
                    'error' => 'O pedido possui itens associados.'
                ], 422);
            }

            $pedido->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pedido removido com sucesso.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao remover pedido', ['loja_id' => $this->getLojaId(), 'pedido_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover pedido.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alterar o status de um pedido
     *
     * Atualiza o status de um pedido específico com base no ID fornecido.
     *
     * @authenticated
     * @url PUT /api/pedidos/mudar/status/{id}
     * @urlParam id integer required O ID do pedido. Exemplo: 1
     * @bodyParam status string required O novo status do pedido. Exemplo: entregue
     * @response 200 {
     *   "id": 1,
     *   "status": "entregue",
     *   "updated_at": "2025-05-28T17:06:00.000000Z"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Pedido não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "status": ["O campo status é obrigatório"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar o status do pedido"
     * }
     */
    public function mudarStatus(Request $request, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $request->validate([
                'status' => 'required|string|max:50',
            ]);

            $pedido = Pedido::where('id', $id)
                ->where('loja_id', $loja_id)
                ->first();

            if (!$pedido) {
                return response()->json(['error' => 'Pedido não encontrado'], 404);
            }

            $pedido->status = $request->status;
            $pedido->save();

            return response()->json([
                'id' => $pedido->id,
                'status' => $pedido->status,
                'updated_at' => $pedido->updated_at,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar o status do pedido'], 500);
        }
    }
}
