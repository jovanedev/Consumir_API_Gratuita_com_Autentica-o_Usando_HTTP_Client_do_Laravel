<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group Fornecedores
 *
 * APIs para gerenciar fornecedores associados a uma loja.
 */
class FornecedorController extends Controller
{
    /**
     * Recupera o loja_id do usuário autenticado.
     *
     * @return int|null O ID da loja associada ao usuário autenticado ou null se não houver.
     */
    private function getLojaId(): ?int
    {
        $user = Auth::user();
        return $user->loja_id;
    }

    /**
     * Valida se o usuário possui uma loja associada.
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
     * Listar fornecedores
     *
     * Retorna uma lista de todos os fornecedores associados à loja do usuário autenticado.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "nome": "Fornecedor Exemplo",
     *       "email": "fornecedor@exemplo.com",
     *       "telefone": "123456789",
     *       "loja_id": 1,
     *       "created_at": "2025-05-28T15:54:00.000000Z",
     *       "updated_at": "2025-05-28T15:54:00.000000Z"
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao listar os fornecedores.",
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

            $fornecedores = Fornecedor::where('loja_id', $loja_id)->get();

            return response()->json([
                'success' => true,
                'data' => $fornecedores
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar os fornecedores.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar um novo fornecedor
     *
     * Cria um novo fornecedor associado à loja do usuário autenticado.
     *
     * @authenticated
     * @bodyParam nome string required Nome do fornecedor. Exemplo: Fornecedor Exemplo
     * @bodyParam email string required E-mail do fornecedor. Exemplo: fornecedor@exemplo.com
     * @bodyParam telefone string required Telefone do fornecedor. Exemplo: 123456789
     * @response 201 {
     *   "id": 1,
     *   "nome": "Fornecedor Exemplo",
     *   "email": "fornecedor@exemplo.com",
     *   "telefone": "123456789",
     *   "loja_id": 1,
     *   "created_at": "2025-05-28T15:54:00.000000Z",
     *   "updated_at": "2025-05-28T15:54:00.000000Z"
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "nome": ["O campo nome é obrigatório."],
     *     "email": ["O campo email é obrigatório."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar fornecedor.",
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

            $request->validate([
                'nome' => 'required|string|max:255',
                'email' => 'required|email|unique:fornecedores,email',
                'telefone' => 'required|string|max:20',
            ]);

            $fornecedor = Fornecedor::create([
                'nome' => $request->nome,
                'email' => $request->email,
                'telefone' => $request->telefone,
                'loja_id' => $loja_id,
            ]);

            return response()->json($fornecedor, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar fornecedor.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir um fornecedor
     *
     * Retorna os detalhes de um fornecedor específico com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id integer required O ID do fornecedor. Exemplo: 1
     * @response 200 {
     *   "id": 1,
     *   "nome": "Fornecedor Exemplo",
     *   "email": "fornecedor@exemplo.com",
     *   "telefone": "123456789",
     *   "loja_id": 1,
     *   "created_at": "2025-05-28T15:54:00.000000Z",
     *   "updated_at": "2025-05-28T15:54:00.000000Z"
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Fornecedor não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar o fornecedor.",
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

            $fornecedor = Fornecedor::where('loja_id', $loja_id)->findOrFail($id);

            return response()->json($fornecedor, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Fornecedor não encontrado.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar o fornecedor.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar um fornecedor
     *
     * Atualiza os dados de um fornecedor existente com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id integer required O ID do fornecedor. Exemplo: 1
     * @bodyParam nome string Nome do fornecedor. Exemplo: Fornecedor Exemplo
     * @bodyParam email string E-mail do fornecedor. Exemplo: fornecedor@exemplo.com
     * @bodyParam telefone string Telefone do fornecedor. Exemplo: 123456789
     * @response 200 {
     *   "id": 1,
     *   "nome": "Fornecedor Exemplo",
     *   "email": "fornecedor@exemplo.com",
     *   "telefone": "123456789",
     *   "loja_id": 1,
     *   "created_at": "2025-05-28T15:54:00.000000Z",
     *   "updated_at": "2025-05-28T15:54:00.000000Z"
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Fornecedor não encontrado."
     * }
     * @responseError 422 {
     *   "error": {
     *     "email": ["O e-mail já está em uso."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar fornecedor.",
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

            $fornecedor = Fornecedor::where('loja_id', $loja_id)->findOrFail($id);

            $request->validate([
                'nome' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:fornecedores,email,' . $id,
                'telefone' => 'sometimes|required|string|max:20',
            ]);

            $fornecedor->update($request->only(['nome', 'email', 'telefone']));

            return response()->json($fornecedor, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Fornecedor não encontrado.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar fornecedor.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover um fornecedor
     *
     * Remove um fornecedor com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id integer required O ID do fornecedor. Exemplo: 1
     * @response 200 {
     *   "message": "Fornecedor removido com sucesso"
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Fornecedor não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover fornecedor.",
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

            $fornecedor = Fornecedor::where('loja_id', $loja_id)->findOrFail($id);

            $fornecedor->delete();

            return response()->json(['message' => 'Fornecedor removido com sucesso'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Fornecedor não encontrado.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover fornecedor.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
