<?php

namespace App\Http\Controllers;

use App\Models\Redirecionamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

/**
 * @group Redirecionamentos
 *
 * APIs para gerenciar redirecionamentos associados a uma loja.
 */
class RedirecionamentoController extends Controller
{
    /**
     * Recupera o loja_id do usuário autenticado
     *
     * @return int|null O ID da loja associada ao usuário autenticado ou null se não houver.
     */
    private function getLojaId(): ?int
    {
        $user = Auth::user();
        return $user->loja_id;
    }

    /**
     * Valida se o usuário possui uma loja associada
     *
     * @return JsonResponse|null Retorna uma resposta JSON de erro se não houver loja associada, ou null se válido.
     */
    private function validateLojaId(): ?JsonResponse
    {
        $loja_id = $this->getLojaId();
        if (!$loja_id) {
            return response()->json(['success' => false, 'message' => 'Usuário não possui loja associada'], 403);
        }
        return null;
    }

    /**
     * Listar redirecionamentos
     *
     * Retorna uma lista de todos os redirecionamentos associados à loja do usuário autenticado.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "message": "Lista de redirecionamentos recuperada com sucesso",
     *   "data": [
     *     {
     *       "id": 1,
     *       "loja_id": 1,
     *       "url_nova": "https://example.com/nova-pagina",
     *       "url_antiga": "https://example.com/pagina-antiga",
     *       "created_at": "2025-05-28T16:02:00.000000Z",
     *       "updated_at": "2025-05-28T16:02:00.000000Z"
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao listar os redirecionamentos.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function index(): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Buscar todos os redirecionamentos relacionados à loja logada
            $redirecionamentos = Redirecionamento::where('loja_id', $loja_id)->get();

            return response()->json([
                'success' => true,
                'message' => 'Lista de redirecionamentos recuperada com sucesso',
                'data' => $redirecionamentos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar os redirecionamentos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar um novo redirecionamento
     *
     * Cria um novo redirecionamento associado à loja do usuário autenticado.
     *
     * @authenticated
     * @bodyParam url_nova string URL para a qual o redirecionamento aponta. Exemplo: https://example.com/nova-pagina
     * @bodyParam url_antiga string URL antiga que será redirecionada. Exemplo: https://example.com/pagina-antiga
     * @response 201 {
     *   "success": true,
     *   "message": "Redirecionamento criado com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "url_nova": "https://example.com/nova-pagina",
     *     "url_antiga": "https://example.com/pagina-antiga",
     *     "created_at": "2025-05-28T16:02:00.000000Z",
     *     "updated_at": "2025-05-28T16:02:00.000000Z"
     *   }
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Erro de validação.",
     *   "errors": {
     *     "_required": ["Pelo menos uma das URLs (nova ou antiga) deve ser fornecida."],
     *     "url_nova": ["A url_nova deve ser uma URL válida."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar o redirecionamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Validação dos dados de entrada
            $request->validate([
                'url_nova' => 'nullable|url',
                'url_antiga' => 'nullable|url',
                '_required' => 'required_without_all:url_nova,url_antiga'
            ], [
                '_required.required_without_all' => 'Pelo menos uma das URLs (nova ou antiga) deve ser fornecida.'
            ]);

            // Criar o redirecionamento com o loja_id do usuário autenticado
            $redirecionamento = Redirecionamento::create([
                'loja_id' => $loja_id,
                'url_nova' => $request->url_nova,
                'url_antiga' => $request->url_antiga,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Redirecionamento criado com sucesso!',
                'data' => $redirecionamento
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar o redirecionamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir um redirecionamento
     *
     * Retorna os detalhes de um redirecionamento específico com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id string required O ID do redirecionamento. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Redirecionamento encontrado com sucesso",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "url_nova": "https://example.com/nova-pagina",
     *     "url_antiga": "https://example.com/pagina-antiga",
     *     "created_at": "2025-05-28T16:02:00.000000Z",
     *     "updated_at": "2025-05-28T16:02:00.000000Z"
     *   }
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Redirecionamento não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar o redirecionamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function show($id): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Busca o redirecionamento por loja_id e id
            $redirecionamento = Redirecionamento::where('loja_id', $loja_id)->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Redirecionamento encontrado com sucesso',
                'data' => $redirecionamento
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Redirecionamento não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar o redirecionamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar um redirecionamento
     *
     * Atualiza os dados de um redirecionamento existente com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id string required O ID do redirecionamento. Exemplo: 1
     * @bodyParam url_nova string URL para a qual o redirecionamento aponta. Exemplo: https://example.com/nova-pagina
     * @bodyParam url_antiga string URL antiga que será redirecionada. Exemplo: https://example.com/pagina-antiga
     * @response 200 {
     *   "success": true,
     *   "message": "Redirecionamento atualizado com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "url_nova": "https://example.com/nova-pagina",
     *     "url_antiga": "https://example.com/pagina-antiga",
     *     "created_at": "2025-05-28T16:02:00.000000Z",
     *     "updated_at": "2025-05-28T16:02:00.000000Z"
     *   }
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Redirecionamento não encontrado."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Erro de validação.",
     *   "errors": {
     *     "_required": ["Pelo menos uma das URLs (nova ou antiga) deve ser fornecida."],
     *     "url_nova": ["A url_nova deve ser uma URL válida."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar o redirecionamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Busca o redirecionamento por loja_id e id
            $redirecionamento = Redirecionamento::where('loja_id', $loja_id)->findOrFail($id);

            // Validação dos dados de entrada
            $request->validate([
                'url_nova' => 'sometimes|nullable|url',
                'url_antiga' => 'sometimes|nullable|url',
                '_required' => 'required_without_all:url_nova,url_antiga'
            ], [
                '_required.required_without_all' => 'Pelo menos uma das URLs (nova ou antiga) deve ser fornecida.'
            ]);

            // Atualiza o redirecionamento
            $redirecionamento->update([
                'url_nova' => $request->url_nova ?? $redirecionamento->url_nova,
                'url_antiga' => $request->url_antiga ?? $redirecionamento->url_antiga,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Redirecionamento atualizado com sucesso!',
                'data' => $redirecionamento
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Redirecionamento não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar o redirecionamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover um redirecionamento
     *
     * Remove um redirecionamento com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id string required O ID do redirecionamento. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Redirecionamento removido com sucesso!"
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Redirecionamento não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover o redirecionamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Busca o redirecionamento por loja_id e id
            $redirecionamento = Redirecionamento::where('loja_id', $loja_id)->findOrFail($id);

            // Apaga o redirecionamento
            $redirecionamento->delete();

            return response()->json([
                'success' => true,
                'message' => 'Redirecionamento removido com sucesso!'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Redirecionamento não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover o redirecionamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
