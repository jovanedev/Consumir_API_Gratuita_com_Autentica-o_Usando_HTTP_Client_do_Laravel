<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group Gestão de Marcas
 *
 * Endpoints para gerenciamento de marcas associadas a lojas.
 */
class MarcaController extends Controller
{
    /**
     * Valida se o usuário está autenticado e possui uma loja associada.
     *
     * @return JsonResponse|null Resposta JSON com erro 401 ou 403 se inválido, ou null se válido.
     */
    private function validateLojaId(): ?JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado.'
            ], 401);
        }
        if (!$user->loja_id) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não possui loja associada.'
            ], 403);
        }
        return null;
    }

    /**
     * Lista todas as marcas da loja autenticada.
     *
     * Retorna uma lista de marcas associadas à loja do usuário autenticado.
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
     *       "nome": "Marca Exemplo",
     *       "descricao": "Descrição da marca"
     *     },
     *     {
     *       "id": 2,
     *       "loja_id": 1,
     *       "nome": "Outra Marca",
     *       "descricao": null
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
     *   "message": "Erro ao listar as marcas.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function index(): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = Auth::user()->loja_id;
            $marcas = Marca::where('loja_id', $loja_id)->get();

            return response()->json([
                'success' => true,
                'data' => $marcas
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar as marcas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe uma marca específica.
     *
     * Retorna os detalhes de uma marca com base no ID fornecido, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID da marca.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "nome": "Marca Exemplo",
     *     "descricao": "Descrição da marca"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para visualizar esta marca."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Marca não encontrada."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar a marca.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function show($id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $user = Auth::user();
            $marca = Marca::findOrFail($id);

            if ($marca->loja_id !== $user->loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para visualizar esta marca.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $marca
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Marca não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar a marca.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria uma nova marca.
     *
     * Cria uma nova marca associada à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @return JsonResponse
     *
     * @bodyParam nome string required Nome da marca (máx. 255 caracteres, único por loja). Exemplo: Marca Exemplo
     * @bodyParam descricao string nullable Descrição da marca. Exemplo: Descrição da marca
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Marca criada com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "nome": "Marca Exemplo",
     *     "descricao": "Descrição da marca"
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
     *     "nome": ["O campo nome é obrigatório", "O nome já está em uso nesta loja"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar a marca.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = Auth::user()->loja_id;
            $validator = Validator::make($request->all(), [
                'nome' => ['required', 'string', 'max:255', "unique:marcas,nome,NULL,id,loja_id,{$loja_id}"],
                'descricao' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validação falhou.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $request->merge(['loja_id' => $loja_id]);
            $marca = Marca::create($request->only(['nome', 'descricao', 'loja_id']));

            return response()->json([
                'success' => true,
                'message' => 'Marca criada com sucesso!',
                'data' => $marca
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar a marca.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza uma marca existente.
     *
     * Atualiza os dados de uma marca específica, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $id O ID da marca.
     * @return JsonResponse
     *
     * @bodyParam nome string required Nome da marca (máx. 255 caracteres, único por loja). Exemplo: Marca Atualizada
     * @bodyParam descricao string nullable Descrição da marca. Exemplo: Nova descrição da marca
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Marca atualizada com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "nome": "Marca Atualizada",
     *     "descricao": "Nova descrição da marca"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para editar esta marca."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Marca não encontrada."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Validação falhou.",
     *   "errors": {
     *     "nome": ["O campo nome é obrigatório", "O nome já está em uso nesta loja"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar a marca.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $user = Auth::user();
            $marca = Marca::findOrFail($id);

            if ($marca->loja_id !== $user->loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar esta marca.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nome' => ['required', 'string', 'max:255', "unique:marcas,nome,{$id},id,loja_id,{$user->loja_id}"],
                'descricao' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validação falhou.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $marca->update($request->only(['nome', 'descricao']));

            return response()->json([
                'success' => true,
                'message' => 'Marca atualizada com sucesso!',
                'data' => $marca
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Marca não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar a marca.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove uma marca.
     *
     * Deleta uma marca específica, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID da marca.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Marca removida com sucesso!"
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para remover esta marca."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Marca não encontrada."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover a marca.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function destroy($id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $user = Auth::user();
            $marca = Marca::findOrFail($id);

            if ($marca->loja_id !== $user->loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para remover esta marca.'
                ], 403);
            }

            $marca->delete();

            return response()->json([
                'success' => true,
                'message' => 'Marca removida com sucesso!'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Marca não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover a marca.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
