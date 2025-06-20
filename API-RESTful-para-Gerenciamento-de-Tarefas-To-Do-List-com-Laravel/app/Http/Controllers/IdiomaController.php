<?php

namespace App\Http\Controllers;

use App\Models\Idioma;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group Gestão de Idiomas
 *
 * Endpoints para gerenciamento de idiomas associados a lojas.
 */
class IdiomaController extends Controller
{
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

        $user = Auth::user();
        if (!$user->loja_id) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não possui loja associada.'
            ], 403);
        }

        return null;
    }

    /**
     * Lista todos os idiomas da loja autenticada.
     *
     * Retorna uma lista de idiomas associados à loja do usuário autenticado.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "message": "Idiomas listados com sucesso.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "loja_id": 1,
     *       "codigo_idioma": "pt-BR",
     *       "created_at": "2025-05-28T16:44:00.000000Z",
     *       "updated_at": "2025-05-28T16:44:00.000000Z"
     *     },
     *     {
     *       "id": 2,
     *       "loja_id": 1,
     *       "codigo_idioma": "en-US",
     *       "created_at": "2025-05-28T16:44:00.000000Z",
     *       "updated_at": "2025-05-28T16:44:00.000000Z"
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
     *   "message": "Erro ao listar os idiomas.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function index(): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $lojaId = Auth::user()->loja_id;
            $idiomas = Idioma::where('loja_id', $lojaId)->get();

            return response()->json([
                'success' => true,
                'message' => 'Idiomas listados com sucesso.',
                'data' => $idiomas
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar os idiomas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe um idioma específico.
     *
     * Retorna os detalhes de um idioma com base no ID fornecido, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID do idioma. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Idioma encontrado com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "codigo_idioma": "pt-BR",
     *     "created_at": "2025-05-28T16:44:00.000000Z",
     *     "updated_at": "2025-05-28T16:44:00.000000Z"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para visualizar este idioma."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Idioma não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar o idioma.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function show(int $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $user = Auth::user();
            $idioma = Idioma::findOrFail($id);

            if ($idioma->loja_id !== $user->loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para visualizar este idioma.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Idioma encontrado com sucesso.',
                'data' => $idioma
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Idioma não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar o idioma.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria um novo idioma.
     *
     * Cria um novo idioma associado à loja do usuário autenticado.
     *
     * @authenticated
     * @bodyParam codigo_idioma string required Código do idioma (máx. 10 caracteres, único por loja, formato: aa-AA ou aa). Exemplo: es-ES
     * @response 201 {
     *   "success": true,
     *   "message": "Idioma criado com sucesso.",
     *   "data": {
     *     "id": 3,
     *     "loja_id": 1,
     *     "codigo_idioma": "es-ES",
     *     "created_at": "2025-05-28T16:44:00.000000Z",
     *     "updated_at": "2025-05-28T16:44:00Z"
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
     *     "codigo_idioma": ["O campo código_idioma é obrigatório.", "O código_idioma já está em uso.", "O código_idioma deve seguir o formato aa-AA ou aa."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar o idioma.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $validatorDictionaryValidator($request->all());

            if ($validator->isInvalid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $idioma = Idioma::create([
                'loja_id' => Auth::user()->loja_id,
                'codigo_idioma' => $request->codigo_idioma
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Idioma criado com sucesso.',
                'data' => $idioma
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar o idioma.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza um idioma existente.
     *
     * Atualiza os dados de um idioma específico, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID do idioma. Exemplo: 1
     * @bodyParam codigo_idioma string|null Código do idioma (máx. 10 caracteres, único por loja, formato: aa-AA ou aa). Exemplo: fr-FR
     * @response 200 {
     *   "success": true,
     *   "message": "Idioma atualizado com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "codigo_idioma": "fr-FR",
     *     "created_at": "2025-05-28T16:44:00.000000Z",
     *     "updated_at": "2025-05-28T16:44:00.000000Z"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para editar este idioma."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Idioma não encontrado."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Erro de validação.",
     *   "errors": {
     *     "codigo_idioma": ["O código_idioma já está em uso.", "O código_idioma deve seguir o formato aa-AA ou aa."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar o idioma.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $user = Auth::user();
            $idioma = Idioma::findOrFail($id);

            if ($idioma->loja_id !== $user->loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar este idioma.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'codigo_idioma' => ['nullable', 'string', 'max:10', "unique:idiomas,codigo_idioma,{$id}", 'regex:/^[a-z]{2}(-[A-Z]{2})?$/i'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $idioma->update($request->only(['codigo_idioma']));

            return response()->json([
                'success' => true,
                'message' => 'Idioma atualizado com sucesso.',
                'data' => $idioma
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Idioma não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar o idioma.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove um idioma.
     *
     * Deleta um idioma específico, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID do idioma. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Idioma removido com sucesso."
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para remover este idioma."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Idioma não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover o idioma.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $user = Auth::user();
            $idioma = Idioma::findOrFail($id);

            if ($idioma->loja_id !== $user->loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para remover este idioma.'
                ], 403);
            }

            $idioma->delete();

            return response()->json([
                'success' => true,
                'message' => 'Idioma removido com sucesso.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Idioma não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover o idioma.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
