<?php

namespace App\Http\Controllers;

use App\Models\Endereco;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group Gestão de Endereços
 *
 * Endpoints para gerenciamento de endereços de usuários.
 */
class EnderecoController extends Controller
{
    /**
     * Valida se o endereço pertence ao usuário autenticado.
     *
     * @param Endereco $endereco O endereço a ser verificado.
     * @return JsonResponse|null Resposta JSON com erro 403 se o usuário não tiver permissão, ou null se válido.
     */
    private function validateOwnership(Endereco $endereco, $id): ?JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 401);
        }
        if ($endereco->usuario_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => "Você não tem permissão para acessar este endereço {$id}."
            ], 403);
        }
        return null;
    }

    /**
     * Lista todos os endereços do usuário autenticado.
     *
     * Retorna uma lista de endereços associados ao usuário autenticado, incluindo informações do usuário relacionado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "usuario_id": 1,
     *       "estado": "São Paulo",
     *       "cidade": "São Paulo",
     *       "bairro": "Centro",
     *       "rua": "Avenida Paulista",
     *       "numero": "123",
     *       "complemento": "Apto 101",
     *       "usuario": {
     *         "id": 1,
     *         "name": "João Silva"
     *       }
     *     }
     *   ]
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado"
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar endereços.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autorizado'
                ], 401);
            }

            $enderecos = Endereco::where('usuario_id', $user->id)->with('usuario')->get();

            return response()->json([
                'success' => true,
                'data' => $enderecos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar endereços.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria um novo endereço.
     *
     * Cria um novo endereço associado a um usuário.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @return JsonResponse
     *
     * @bodyParam usuario_id integer required O ID do usuário (deve existir na tabela users). Exemplo: 1
     * @bodyParam estado string required O estado do endereço (máx. 100 caracteres). Exemplo: São Paulo
     * @bodyParam cidade string required A cidade do endereço (máx. 100 caracteres). Exemplo: São Paulo
     * @bodyParam bairro string required O bairro do endereço (máx. 100 caracteres). Exemplo: Centro
     * @bodyParam rua string required A rua do endereço (máx. 100 caracteres). Exemplo: Avenida Paulista
     * @bodyParam numero string required O número do endereço (máx. 20 caracteres). Exemplo: 123
     * @bodyParam complemento string nullable O complemento do endereço (máx. 255 caracteres). Exemplo: Apto 101
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Endereço criado com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "usuario_id": 1,
     *     "estado": "São Paulo",
     *     "cidade": "São Paulo",
     *     "bairro": "Centro",
     *     "rua": "Avenida Paulista",
     *     "numero": "123",
     *     "complemento": "Apto 101"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado"
     * }
     * @responseError 422 {
     *   "success": false,
     *   "errors": {
     *     "usuario_id": ["O campo usuario_id é obrigatório", "O usuario_id deve existir na tabela users"],
     *     "estado": ["O campo estado é obrigatório"],
     *     "cidade": ["O campo cidade é obrigatório"],
     *     "bairro": ["O campo bairro é obrigatório"],
     *     "rua": ["O campo rua é obrigatório"],
     *     "numero": ["O campo número é obrigatório"],
     *     "complemento": ["O campo complemento deve ser uma string"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar endereço.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autorizado'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'usuario_id' => 'required|exists:users,id',
                'estado' => 'required|string|max:100',
                'cidade' => 'required|string|max:100',
                'bairro' => 'required|string|max:100',
                'rua' => 'required|string|max:100',
                'numero' => 'required|string|max:20',
                'complemento' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->usuario_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para criar um endereço para outro usuário.'
                ], 403);
            }

            $endereco = Endereco::create($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Endereço criado com sucesso.',
                'data' => $endereco
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar endereço.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe um endereço específico.
     *
     * Retorna os detalhes de um endereço com base no ID fornecido, restrito ao usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID do endereço.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "usuario_id": 1,
     *     "estado": "São Paulo",
     *     "cidade": "São Paulo",
     *     "bairro": "Centro",
     *     "rua": "Avenida Paulista",
     *     "numero": "123",
     *     "complemento": "Apto 101",
     *     "usuario": {
     *       "id": 1,
     *       "name": "João Silva"
     *     }
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado"
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para acessar este endereço."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Endereço não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar endereço.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function show($id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autorizado'
                ], 401);
            }

            $endereco = Endereco::with('usuario')->findOrFail($id);

            if ($errorResponse = $this->validateOwnership($endereco, $id)) {
                return $errorResponse;
            }

            return response()->json([
                'success' => true,
                'data' => $endereco
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Endereço não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar endereço.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza um endereço existente.
     *
     * Atualiza os dados de um endereço específico, restrito ao usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $id O ID do endereço.
     * @return JsonResponse
     *
     * @bodyParam estado string O estado do endereço (máx. 100 caracteres). Exemplo: Rio de Janeiro
     * @bodyParam cidade string A cidade do endereço (máx. 100 caracteres). Exemplo: Rio de Janeiro
     * @bodyParam bairro string O bairro do endereço (máx. 100 caracteres). Exemplo: Copacabana
     * @bodyParam rua string A rua do endereço (máx. 100 caracteres). Exemplo: Avenida Atlântica
     * @bodyParam numero string O número do endereço (máx. 20 caracteres). Exemplo: 456
     * @bodyParam complemento string nullable O complemento do endereço (máx. 255 caracteres). Exemplo: Casa 2
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Endereço atualizado com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "usuario_id": 1,
     *     "estado": "Rio de Janeiro",
     *     "cidade": "Rio de Janeiro",
     *     "bairro": "Copacabana",
     *     "rua": "Avenida Atlântica",
     *     "numero": "456",
     *     "complemento": "Casa 2"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado"
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para acessar este endereço."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Endereço não encontrado."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "errors": {
     *     "estado": ["O campo estado é obrigatório"],
     *     "cidade": ["O campo cidade é obrigatório"],
     *     "bairro": ["O campo bairro é obrigatório"],
     *     "rua": ["O campo rua é obrigatório"],
     *     "numero": ["O campo número é obrigatório"],
     *     "complemento": ["O campo complemento deve ser uma string"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar endereço.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autorizado'
                ], 401);
            }

            $endereco = Endereco::findOrFail($id);

            if ($errorResponse = $this->validateOwnership($endereco, $id)) {
                return $errorResponse;
            }

            $validator = Validator::make($request->all(), [
                'estado' => 'sometimes|required|string|max:100',
                'cidade' => 'sometimes|required|string|max:100',
                'bairro' => 'sometimes|required|string|max:100',
                'rua' => 'sometimes|required|string|max:100',
                'numero' => 'sometimes|required|string|max:20',
                'complemento' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $endereco->update($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Endereço atualizado com sucesso.',
                'data' => $endereco
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Endereço não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar endereço.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove um endereço.
     *
     * Deleta um endereço específico, restrito ao usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID do endereço.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Endereço deletado com sucesso."
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado"
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para acessar este endereço."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Endereço não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao deletar endereço.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autorizado'
                ], 401);
            }

            $endereco = Endereco::findOrFail($id);

            if ($errorResponse = $this->validateOwnership($endereco, $id)) {
                return $errorResponse;
            }

            $endereco->delete();
            return response()->json([
                'success' => true,
                'message' => 'Endereço deletado com sucesso.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Endereço não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar endereço.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
