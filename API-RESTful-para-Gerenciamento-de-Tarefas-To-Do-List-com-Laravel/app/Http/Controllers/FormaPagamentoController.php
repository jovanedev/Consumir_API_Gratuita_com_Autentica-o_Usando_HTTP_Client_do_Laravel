<?php

namespace App\Http\Controllers;

use App\Models\FormaPagamento;
use App\Models\MeioPagamento;
use App\Models\Loja;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group Gestão de Formas de Pagamento
 *
 * Endpoints para gerenciamento de formas de pagamento associadas a lojas.
 */
class FormaPagamentoController extends Controller
{
    /**
     * Valida se o usuário possui uma loja associada.
     *
     * @return JsonResponse|null Resposta JSON com erro 403 se não houver loja associada, ou null se válido.
     */
    private function validateLojaId(): ?JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }
        if (!$user->loja) {
            return response()->json([
                'success' => false,
                'message' => 'Loja não associada ao usuário.'
            ], 403);
        }
        return null;
    }

    /**
     * Valida se a forma de pagamento pertence à loja do usuário autenticado.
     *
     * @param FormaPagamento $forma A forma de pagamento a ser verificada.
     * @param int $id O ID da forma de pagamento.
     * @return JsonResponse|null Resposta JSON com erro 403 se o usuário não tiver permissão, ou null se válido.
     */
    private function validateOwnership(FormaPagamento $forma, $id): ?JsonResponse
    {
        $user = Auth::user();
        if ($forma->loja_id !== $user->loja->id) {
            return response()->json([
                'success' => false,
                'message' => "Você não tem permissão para acessar esta forma de pagamento {$id}."
            ], 403);
        }
        return null;
    }

    /**
     * Lista todas as formas de pagamento da loja autenticada.
     *
     * Retorna uma lista de formas de pagamento associadas à loja do usuário autenticado, incluindo informações do meio de pagamento e da loja.
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
     *       "meio_pagamentos_id": 1,
     *       "dados_conta": "Chave PIX: 123456789",
     *       "meio_pagamento": {
     *         "id": 1,
     *         "nome": "PIX"
     *       },
     *       "loja": {
     *         "id": 1,
     *         "nome": "Loja Exemplo"
     *       }
     *     }
     *   ]
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Usuário não autenticado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Loja não associada ao usuário."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao listar formas de pagamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function index(): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = Auth::user()->loja->id;

            $formas = FormaPagamento::with(['meioPagamento', 'loja'])->where('loja_id', $loja_id)->get();

            return response()->json([
                'success' => true,
                'data' => $formas
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar formas de pagamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe uma forma de pagamento específica.
     *
     * Retorna os detalhes de uma forma de pagamento com base no ID fornecido, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID da forma de pagamento.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "meio_pagamentos_id": 1,
     *     "dados_conta": "Chave PIX: 123456789",
     *     "meio_pagamento": {
     *       "id": 1,
     *       "nome": "PIX"
     *     },
     *     "loja": {
     *       "id": 1,
     *       "nome": "Loja Exemplo"
     *     }
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Usuário não autenticado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para acessar esta forma de pagamento."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Forma de pagamento não encontrada."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar forma de pagamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function show($id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $forma = FormaPagamento::with(['meioPagamento', 'loja'])->findOrFail($id);

            if ($errorResponse = $this->validateOwnership($forma, $id)) {
                return $errorResponse;
            }

            return response()->json([
                'success' => true,
                'data' => $forma
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Forma de pagamento não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar forma de pagamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria uma nova forma de pagamento.
     *
     * Cria uma nova forma de pagamento associada à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @return JsonResponse
     *
     * @bodyParam loja_id integer required O ID da loja (deve existir na tabela lojas). Exemplo: 1
     * @bodyParam meio_pagamentos_id integer required O ID do meio de pagamento (deve existir na tabela meio_pagamentos). Exemplo: 1
     * @bodyParam dados_conta string required Dados da conta para o meio de pagamento (e.g., chave PIX, dados bancários). Exemplo: Chave PIX: 123456789
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "meio_pagamentos_id": 1,
     *     "dados_conta": "Chave PIX: 123456789"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Usuário não autenticado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para criar uma forma de pagamento para esta loja."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "errors": {
     *     "loja_id": ["O campo loja_id é obrigatório", "O loja_id deve existir na tabela lojas"],
     *     "meio_pagamentos_id": ["O campo meio_pagamentos_id é obrigatório", "O meio_pagamentos_id deve existir na tabela meio_pagamentos"],
     *     "dados_conta": ["O campo dados_conta é obrigatório"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar forma de pagamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $user = Auth::user();
            if ($request->loja_id != $user->loja->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para criar uma forma de pagamento para esta loja.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'loja_id' => 'required|exists:lojas,id',
                'meio_pagamentos_id' => 'required|exists:meio_pagamentos,id',
                'dados_conta' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $formaPagamento = FormaPagamento::create($request->all());
            return response()->json([
                'success' => true,
                'data' => $formaPagamento
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar forma de pagamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza uma forma de pagamento existente.
     *
     * Atualiza os dados de uma forma de pagamento específica, restrita à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $id O ID da forma de pagamento.
     * @return JsonResponse
     *
     * @bodyParam loja_id integer O ID da loja (deve existir na tabela lojas). Exemplo: 1
     * @bodyParam meio_pagamentos_id integer O ID do meio de pagamento (deve existir na tabela meio_pagamentos). Exemplo: 2
     * @bodyParam dados_conta string Dados da conta para o meio de pagamento (e.g., chave PIX, dados bancários). Exemplo: Conta Bancária: 1234-5
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "meio_pagamentos_id": 2,
     *     "dados_conta": "Conta Bancária: 1234-5"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Usuário não autenticado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para acessar esta forma de pagamento."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Forma de pagamento não encontrada."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "errors": {
     *     "loja_id": ["O campo loja_id deve existir na tabela lojas"],
     *     "meio_pagamentos_id": ["O campo meio_pagamentos_id deve existir na tabela meio_pagamentos"],
     *     "dados_conta": ["O campo dados_conta é obrigatório"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar forma de pagamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $formaPagamento = FormaPagamento::findOrFail($id);

            if ($errorResponse = $this->validateOwnership($formaPagamento, $id)) {
                return $errorResponse;
            }

            $validator = Validator::make($request->all(), [
                'loja_id' => 'sometimes|required|exists:lojas,id',
                'meio_pagamentos_id' => 'sometimes|required|exists:meio_pagamentos,id',
                'dados_conta' => 'sometimes|required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->has('loja_id') && $request->loja_id != Auth::user()->loja->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para atualizar a forma de pagamento para outra loja.'
                ], 403);
            }

            $formaPagamento->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $formaPagamento
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Forma de pagamento não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar forma de pagamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove uma forma de pagamento.
     *
     * Deleta uma forma de pagamento específica, restrita à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID da forma de pagamento.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Forma de pagamento removida com sucesso."
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Usuário não autenticado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para acessar esta forma de pagamento."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Forma de pagamento não encontrada."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover forma de pagamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function destroy($id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $formaPagamento = FormaPagamento::findOrFail($id);

            if ($errorResponse = $this->validateOwnership($formaPagamento, $id)) {
                return $errorResponse;
            }

            $formaPagamento->delete();

            return response()->json([
                'success' => true,
                'message' => 'Forma de pagamento removida com sucesso.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Forma de pagamento não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover forma de pagamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
