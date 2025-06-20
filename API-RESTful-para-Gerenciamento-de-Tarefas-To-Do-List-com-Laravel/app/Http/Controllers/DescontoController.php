<?php

namespace App\Http\Controllers;

use App\Models\Desconto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group Descontos
 *
 * Endpoints para gerenciamento de descontos associados a uma loja.
 */
class DescontoController extends Controller
{
    /**
     * Recupera o ID da loja do usuário autenticado.
     *
     * @return int|null O ID da loja ou null se não encontrado.
     */
    private function getLojaId(): ?int
    {
        $user = Auth::user();
        return $user ? $user->id_loja : null; // Ajustado para id_loja
    }

    /**
     * Valida se o usuário está autenticado e possui uma loja associada.
     *
     * @return JsonResponse|null Resposta JSON com erro 401 ou 403 se inválido, ou null se válido.
     */
    private function validateLojaId(): ?JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado.'
            ], 401);
        }

        $lojaId = $this->getLojaId();
        if (!$lojaId) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não possui loja associada.'
            ], 403);
        }

        return null;
    }

    /**
     * Lista todos os descontos da loja autenticada.
     *
     * Retorna uma lista de descontos associados à loja do usuário autenticado.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "message": "Descontos listados com sucesso.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "loja_id": 1,
     *       "codigo": "DESC10",
     *       "tipo": "percentagem",
     *       "valor": 10.00,
     *       "data_inicio": "2025-05-28",
     *       "data_fim": "2025-06-28",
     *       "status": "ativo",
     *       "created_at": "2025-05-28T17:00:00.000000Z",
     *       "updated_at": "2025-05-28T17:00:00.000000Z"
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
     *   "message": "Erro ao listar descontos.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function index(): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $lojaId = $this->getLojaId();

            $descontos = Desconto::where('loja_id', $lojaId)->get();

            return response()->json([
                'success' => true,
                'message' => 'Descontos listados com sucesso.',
                'data' => $descontos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar descontos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe um desconto específico.
     *
     * Retorna os detalhes de um desconto com base no ID, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID do desconto. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Desconto encontrado com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "codigo": "DESC10",
     *     "tipo": "percentagem",
     *     "valor": 10.00,
     *     "data_inicio": "2025-05-28",
     *     "data_fim": "2025-06-28",
     *     "status": "ativo",
     *     "created_at": "2025-05-28T17:00:00.000000Z",
     *     "updated_at": "2025-05-28T17:00:00.000000Z"
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
     * @responseError 404 {
     *   "success": false,
     *   "message": "Desconto não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar desconto.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function show(int $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $lojaId = $this->getLojaId();

            $desconto = Desconto::where('loja_id', $lojaId)->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Desconto encontrado com sucesso.',
                'data' => $desconto
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Desconto não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar desconto.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria um novo desconto.
     *
     * Cria um desconto associado à loja do usuário autenticado.
     *
     * @authenticated
     * @bodyParam codigo string required Código único do desconto (máx. 255 caracteres). Exemplo: DESC10
     * @bodyParam tipo string required Tipo do desconto (percentagem ou dinheiro). Exemplo: percentagem
     * @bodyParam valor number required Valor do desconto (mín. 0). Exemplo: 10.00
     * @bodyParam data_inicio string required Data de início (formato Y-m-d). Exemplo: 2025-05-28
     * @bodyParam data_fim string|null Data de término (deve ser igual ou após data_inicio). Exemplo: 2025-06-28
     * @bodyParam status string required Status do desconto (ativo ou inativo). Exemplo: ativo
     * @response 201 {
     *   "success": true,
     *   "message": "Desconto criado com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "codigo": "DESC10",
     *     "tipo": "percentagem",
     *     "valor": 10.00,
     *     "data_inicio": "2025-05-28",
     *     "data_fim": "2025-06-28",
     *     "status": "ativo",
     *     "created_at": "2025-05-28T17:00:00.000000Z",
     *     "updated_at": "2025-05-28T17:00:00.000000Z"
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
     *   "message": "Erro de validação.",
     *   "errors": {
     *     "codigo": ["O código é obrigatório.", "O código já está em uso."],
     *     "tipo": ["O tipo deve ser percentagem ou dinheiro."],
     *     "valor": ["O valor deve ser numérico e maior ou igual a 0."],
     *     "data_inicio": ["A data de início é obrigatória.", "A data de início deve ser uma data válida."],
     *     "data_fim": ["A data de término deve ser igual ou após a data de início."],
     *     "status": ["O status deve ser ativo ou inativo."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar desconto.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $lojaId = $this->getLojaId();

            $validator = Validator::make($request->all(), [
                'codigo' => 'required|string|max:255|unique:descontos,codigo',
                'tipo' => 'required|in:percentagem,dinheiro',
                'valor' => 'required|numeric|min:0',
                'data_inicio' => 'required|date|date_format:Y-m-d',
                'data_fim' => 'nullable|date|date_format:Y-m-d|after_or_equal:data_inicio',
                'status' => 'required|in:ativo,inativo',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $desconto = Desconto::create([
                'codigo' => $request->codigo,
                'tipo' => $request->tipo,
                'valor' => $request->valor,
                'data_inicio' => $request->data_inicio,
                'data_fim' => $request->data_fim,
                'status' => $request->status,
                'loja_id' => $lojaId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Desconto criado com sucesso.',
                'data' => $desconto
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar desconto.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza um desconto existente.
     *
     * Atualiza os detalhes de um desconto específico, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID do desconto. Exemplo: 1
     * @bodyParam codigo string required Código único do desconto (máx. 255 caracteres). Exemplo: DESC20
     * @bodyParam tipo string required Tipo do desconto (percentagem ou dinheiro). Exemplo: dinheiro
     * @bodyParam valor number required Valor do desconto (mín. 0). Exemplo: 20.00
     * @bodyParam data_inicio string required Data de início (formato Y-m-d). Exemplo: 2025-05-28
     * @bodyParam data_fim string|null Data de término (deve ser igual ou após data_inicio). Exemplo: 2025-06-28
     * @bodyParam status string required Status do desconto (ativo ou inativo). Exemplo: inativo
     * @response 200 {
     *   "success": true,
     *   "message": "Desconto atualizado com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "codigo": "DESC20",
     *     "tipo": "dinheiro",
     *     "valor": 20.00,
     *     "data_inicio": "2025-05-28",
     *     "data_fim": "2025-06-28",
     *     "status": "inativo",
     *     "created_at": "2025-05-28T17:00:00.000000Z",
     *     "updated_at": "2025-05-28T17:00:00.000000Z"
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
     * @responseError 404 {
     *   "success": false,
     *   "message": "Desconto não encontrado."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Erro de validação.",
     *   "errors": {
     *     "codigo": ["O código já está em uso."],
     *     "tipo": ["O tipo deve ser percentagem ou dinheiro."],
     *     "valor": ["O valor deve ser numérico e maior ou igual a 0."],
     *     "data_inicio": ["A data de início deve ser uma data válida."],
     *     "data_fim": ["A data de término deve ser igual ou após a data de início."],
     *     "status": ["O status deve ser ativo ou inativo."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar desconto.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $lojaId = $this->getLojaId();

            $desconto = Desconto::where('loja_id', $lojaId)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'codigo' => 'required|string|max:255|unique:descontos,codigo,' . $id,
                'tipo' => 'required|in:percentagem,dinheiro',
                'valor' => 'required|numeric|min:0',
                'data_inicio' => 'required|date|date_format:Y-m-d',
                'data_fim' => 'nullable|date|date_format:Y-m-d|after_or_equal:data_inicio',
                'status' => 'required|in:ativo,inativo',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $desconto->update([
                'codigo' => $request->codigo,
                'tipo' => $request->tipo,
                'valor' => $request->valor,
                'data_inicio' => $request->data_inicio,
                'data_fim' => $request->data_fim,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Desconto atualizado com sucesso.',
                'data' => $desconto
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Desconto não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar desconto.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove um desconto.
     *
     * Remove um desconto específico, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID do desconto. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Desconto removido com sucesso."
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Desconto não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover desconto.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $lojaId = $this->getLojaId();

            $desconto = Desconto::where('loja_id', $lojaId)->findOrFail($id);

            $desconto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Desconto removido com sucesso.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Desconto não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover desconto.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
