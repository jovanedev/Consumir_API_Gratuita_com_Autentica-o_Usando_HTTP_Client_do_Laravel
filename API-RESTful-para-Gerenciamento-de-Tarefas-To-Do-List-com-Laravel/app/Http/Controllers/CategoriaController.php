<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group Categorias
 *
 * Endpoints para gerenciamento de categorias associadas a uma loja.
 */
class CategoriaController extends Controller
{
    /**
     * Recupera o ID da loja do usuário autenticado.
     *
     * @return int|null O ID da loja ou null se não encontrado.
     */
    private function getLojaId(): ?int
    {
        $user = Auth::user();
        return $user ? $user->id_loja : null;
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
     * Lista todas as categorias da loja autenticada.
     *
     * Retorna uma lista de categorias pertencentes à loja do usuário autenticado.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "message": "Categorias listadas com sucesso.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "loja_id": 1,
     *       "nome": "Categoria Exemplo",
     *       "descricao": "Descrição da categoria exemplo",
     *       "status": true,
     *       "created_at": "2025-05-28T20:00:00.000000Z",
     *       "updated_at": "2025-05-28T20:00:00.000000Z"
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
     *   "message": "Erro ao listar categorias.",
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

            $categorias = Categoria::where('loja_id', $lojaId)->get();

            return response()->json([
                'success' => true,
                'message' => 'Categorias listadas com sucesso.',
                'data' => $categorias
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar categorias.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria uma nova categoria.
     *
     * Cria uma categoria associada à loja do usuário autenticado.
     *
     * @authenticated
     * @bodyParam nome string required O nome da categoria (máx. 255 caracteres). Exemplo: Categoria Exemplo
     * @bodyParam descricao string|null A descrição da categoria. Exemplo: Descrição da categoria exemplo
     * @bodyParam status boolean|null O status da categoria (padrão: false). Exemplo: true
     * @response 201 {
     *   "success": true,
     *   "message": "Categoria criada com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "nome": "Categoria Exemplo",
     *     "descricao": "Descrição da categoria exemplo",
     *     "status": true,
     *     "created_at": "2025-05-28T20:00:00.000000Z",
     *     "updated_at": "2025-05-28T20:00:00.000000Z"
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
     *     "nome": ["O campo nome é obrigatório.", "O nome deve ter no máximo 255 caracteres."],
     *     "descricao": ["A descrição deve ser uma string."],
     *     "status": ["O status deve ser um booleano."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar categoria.",
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
                'nome' => 'required|string|max:255',
                'descricao' => 'nullable|string',
                'status' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $categoria = Categoria::create([
                'nome' => $request->nome,
                'descricao' => $request->descricao,
                'status' => $request->status ?? false,
                'loja_id' => $lojaId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoria criada com sucesso.',
                'data' => $categoria
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar categoria.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe uma categoria específica.
     *
     * Retorna os detalhes de uma categoria com base no ID, restrita à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID da categoria. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Categoria encontrada com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "nome": "Categoria Exemplo",
     *     "descricao": "Descrição da categoria",
     *     "status": true,
     *     "created_at": "2025-05-28T20:00:00.000000Z",
     *     "updated_at": "2025-05-28T20:00:00.000000Z"
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
     *   "message": "Categoria não encontrada."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar categoria.",
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

            $categoria = Categoria::where('loja_id', $lojaId)->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Categoria encontrada com sucesso.',
                'data' => $categoria
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoria não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar categoria.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza uma categoria existente.
     *
     * Atualiza os detalhes de uma categoria específica, restrita à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID da categoria. Exemplo: 1
     * @bodyParam nome string|null O nome da categoria (máx. 255 caracteres). Exemplo: Categoria Atualizada
     * @bodyParam descricao string|null A descrição da categoria. Exemplo: Nova descrição
     * @bodyParam status boolean|null O status da categoria. Exemplo: false
     * @response 200 {
     *   "success": true,
     *   "message": "Categoria atualizada com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "nome": "Categoria Atualizada",
     *     "descricao": "Nova descrição",
     *     "status": false,
     *     "created_at": "2025-05-28T20:00:00.000000Z",
     *     "updated_at": "2025-05-28T20:00:00.000000Z"
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
     *   "message": "Categoria não encontrada."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Erro de validação.",
     *   "errors": {
     *     "nome": ["O nome deve ter no máximo 255 caracteres."],
     *     "descricao": ["A descrição deve ser uma string."],
     *     "status": ["O status deve ser um booleano."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar categoria.",
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

            $categoria = Categoria::where('loja_id', $lojaId)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nome' => 'sometimes|required|string|max:255',
                'descricao' => 'nullable|string',
                'status' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $categoria->update([
                'nome' => $request->nome ?? $categoria->nome,
                'descricao' => $request->descricao ?? $categoria->descricao,
                'status' => $request->has('status') ? $request->status : $categoria->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoria atualizada com sucesso.',
                'data' => $categoria
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoria não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar categoria.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove uma categoria.
     *
     * Remove uma categoria específica, restrita à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID da categoria. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Categoria removida com sucesso."
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
     *   "message": "Categoria não encontrada."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover categoria.",
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

            $categoria = Categoria::where('loja_id', $lojaId)->findOrFail($id);

            $categoria->delete();

            return response()->json([
                'success' => true,
                'message' => 'Categoria removida com sucesso.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoria não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover categoria.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
