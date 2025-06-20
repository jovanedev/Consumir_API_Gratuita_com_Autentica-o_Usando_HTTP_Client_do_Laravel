<?php

namespace App\Http\Controllers;

use App\Models\Dominio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group Gestão de Domínios
 *
 * Endpoints para gerenciamento de domínios associados a lojas.
 */
class DominioController extends Controller
{
    /**
     * Recupera o ID da loja do usuário autenticado.
     *
     * @return int|null O ID da loja ou null se não encontrado.
     */
    private function getLojaId(): ?int
    {
        $user = Auth::user();
        return $user->loja_id;
    }

    /**
     * Valida se o usuário possui uma loja associada.
     *
     * @return JsonResponse|null Resposta JSON com erro 403 se não houver loja associada, ou null se válido.
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
     * Lista todos os domínios da loja autenticada.
     *
     * Retorna uma lista de domínios associados à loja do usuário autenticado.
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
     *       "dominio": "exemplo.com",
     *       "principal": true,
     *       "status_dominio": "ativo",
     *       "status_ssl": "configurado"
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao listar os domínios.",
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

            $dominios = Dominio::where('loja_id', $loja_id)->get();

            return response()->json([
                'success' => true,
                'data' => $dominios
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar os domínios.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe um domínio específico.
     *
     * Retorna os detalhes de um domínio com base no ID fornecido, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID do domínio.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "dominio": "exemplo.com",
     *     "principal": true,
     *     "status_dominio": "ativo",
     *     "status_ssl": "configurado"
     *   }
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Domínio não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar o domínio.",
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

            $dominio = Dominio::where('loja_id', $loja_id)->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $dominio
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Domínio não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar o domínio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria um novo domínio.
     *
     * Cria um novo domínio associado à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @return JsonResponse
     *
     * @bodyParam dominio string required O nome do domínio (deve ser único). Exemplo: exemplo.com
     * @bodyParam principal boolean nullable Indica se o domínio é o principal (padrão: false). Exemplo: true
     * @bodyParam status_dominio string required Status do domínio (e.g., ativo, inativo). Exemplo: ativo
     * @bodyParam status_ssl string required Status do SSL (e.g., configurado, pendente). Exemplo: configurado
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Domínio criado com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "dominio": "exemplo.com",
     *     "principal": true,
     *     "status_dominio": "ativo",
     *     "status_ssl": "configurado"
     *   }
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Validação falhou.",
     *   "errors": {
     *     "dominio": ["O campo dominio é obrigatório", "O dominio já está em uso"],
     *     "status_dominio": ["O campo status_dominio é obrigatório"],
     *     "status_ssl": ["O campo status_ssl é obrigatório"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar o domínio.",
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
                'dominio' => 'required|string|unique:dominios,dominio',
                'principal' => 'nullable|boolean',
                'status_dominio' => 'required|string',
                'status_ssl' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validação falhou.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dominio = Dominio::create([
                'loja_id' => $loja_id,
                'dominio' => $request->dominio,
                'principal' => $request->principal ?? false,
                'status_dominio' => $request->status_dominio,
                'status_ssl' => $request->status_ssl,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Domínio criado com sucesso!',
                'data' => $dominio
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar o domínio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza um domínio existente.
     *
     * Atualiza os dados de um domínio específico, restrito à loja do usuário autenticado.
     *
 evidences @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $id O ID do domínio.
     * @return JsonResponse
     *
     * @bodyParam dominio string O nome do domínio (deve ser único). Exemplo: novoexemplo.com
     * @bodyParam principal boolean Indica se o domínio é o principal. Exemplo: false
     * @bodyParam status_dominio string Status do domínio (e.g., ativo, inativo). Exemplo: inativo
     * @bodyParam status_ssl string Status do SSL (e.g., configurado, pendente). Exemplo: pendente
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Domínio atualizado com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "dominio": "novoexemplo.com",
     *     1 => 'principal',
     *     'status_dominio' => false,
     *     status_ssl": "pendente"
     *   }
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Domínio não encontrado."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Validação falhou.",
     *   "errors": {
     *     "dominio": ["O campo dominio é obrigatório"],
     *     "status_dominio": ["O campo status_dominio é obrigatório"],
     *     "status_ssl": ["O campo status_ssl é obrigatório"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar o domínio.",
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

            $dominio = Dominio::where('loja_id', $loja_id)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'dominio' => 'sometimes|required|string|unique:dominios,dominio,' . $id,
                'principal' => 'nullable|boolean',
                'status_dominio' => 'sometimes|required|string',
                'status_ssl' => 'sometimes|required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validação falhou.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dominio->update([
                'dominio' => $request->dominio ?? $dominio->dominio,
                'principal' => $request->principal ?? $dominio->principal,
                'status_dominio' => $request->status_dominio ?? $dominio->status_dominio,
                'status_ssl' => $request->status_ssl ?? $dominio->status_ssl,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Domínio atualizado com sucesso!',
                'data' => $dominio
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validação falhou.',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Domínio não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar o domínio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove um domínio.
     *
     * Deleta um domínio específico, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID do domínio.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Domínio removido com sucesso!"
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Domínio não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover o domínio.",
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

            $dominio = Dominio::where('loja_id', $loja_id)->findOrFail($id);

            $dominio->delete();

            return response()->json([
                'success' => true,
                'message' => 'Domínio removido com sucesso!'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Domínio não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover o domínio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
