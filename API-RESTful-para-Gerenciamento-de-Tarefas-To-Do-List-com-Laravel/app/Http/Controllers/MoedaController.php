<?php

namespace App\Http\Controllers;

use App\Models\Moeda;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group Gestão de Moedas
 *
 * Endpoints para gerenciamento de moedas associadas a lojas.
 */
class MoedaController extends Controller
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
     * Lista todas as moedas da loja autenticada.
     *
     * Retorna uma lista de moedas associadas à loja do usuário autenticado.
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
     *       "nome": "Real Brasileiro",
     *       "codigo": "BRL",
     *       "simbolo": "R$",
     *       "taxa_cambio": 1.0,
     *       "padrao": true,
     *       "status": true
     *     },
     *     {
     *       "id": 2,
     *       "loja_id": 1,
     *       "nome": "Dólar Americano",
     *       "codigo": "USD",
     *       "simbolo": "$",
     *       "taxa_cambio": 5.5,
     *       "padrao": false,
     *       "status": true
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
     *   "message": "Erro ao listar as moedas.",
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
            $moedas = Moeda::where('loja_id', $loja_id)->get();

            return response()->json([
                'success' => true,
                'data' => $moedas
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar as moedas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe uma moeda específica.
     *
     * Retorna os detalhes de uma moeda com base no ID fornecido, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID da moeda.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "nome": "Real Brasileiro",
     *     "codigo": "BRL",
     *     "simbolo": "R$",
     *     "taxa_cambio": 1.0,
     *     "padrao": true,
     *     "status": true
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para visualizar esta moeda."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Moeda não encontrada."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar a moeda.",
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
            $moeda = Moeda::findOrFail($id);

            if ($moeda->loja_id !== $user->loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para visualizar esta moeda.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $moeda
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Moeda não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar a moeda.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria uma nova moeda.
     *
     * Cria uma nova moeda associada à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @return JsonResponse
     *
     * @bodyParam nome string required Nome da moeda (máx. 255 caracteres, único por loja). Exemplo: Real Brasileiro
     * @bodyParam codigo string required Código da moeda (máx. 255 caracteres, único por loja). Exemplo: BRL
     * @bodyParam simbolo string required Símbolo da moeda (máx. 10 caracteres). Exemplo: R$
     * @bodyParam taxa_cambio numeric required Taxa de câmbio em relação à moeda padrão (mín. 0). Exemplo: 1.0
     * @bodyParam padrao boolean nullable Indica se é a moeda padrão da loja. Exemplo: true
     * @bodyParam status boolean nullable Indica se a moeda está ativa. Exemplo: true
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Moeda criada com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "nome": "Real Brasileiro",
     *     "codigo": "BRL",
     *     "simbolo": "R$",
     *     "taxa_cambio": 1.0,
     *     "padrao": true,
     *     "status": true
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
     *     "nome": ["O campo nome é obrigatório", "O nome já está em uso nesta loja"],
     *     "codigo": ["O código já está em uso nesta loja"],
     *     "simbolo": ["O campo simbolo é obrigatório"],
     *     "taxa_cambio": ["O campo taxa_cambio deve ser numérico e não negativo"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar a moeda.",
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
                'nome' => ['required', 'string', 'max:255', "unique:moedas,nome,NULL,id,loja_id,{$loja_id}"],
                'codigo' => ['required', 'string', 'max:255', "unique:moedas,codigo,NULL,id,loja_id,{$loja_id}"],
                'simbolo' => 'required|string|max:10',
                'taxa_cambio' => 'required|numeric|min:0',
                'padrao' => 'nullable|boolean',
                'status' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validação falhou.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $request->merge(['loja_id' => $loja_id]);
            $moeda = Moeda::create($request->only([
                'nome', 'codigo', 'simbolo', 'taxa_cambio', 'padrao', 'status', 'loja_id'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Moeda criada com sucesso!',
                'data' => $moeda
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar a moeda.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza uma moeda existente.
     *
     * Atualiza os dados de uma moeda específica, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $id O ID da moeda.
     * @return JsonResponse
     *
     * @bodyParam nome string required Nome da moeda (máx. 255 caracteres, único por loja). Exemplo: Dólar Americano
     * @bodyParam codigo string required Código da moeda (máx. 255 caracteres, único por loja). Exemplo: USD
     * @bodyParam simbolo string required Símbolo da moeda (máx. 10 caracteres). Exemplo: $
     * @bodyParam taxa_cambio numeric required Taxa de câmbio em relação à moeda padrão (mín. 0). Exemplo: 5.5
     * @bodyParam padrao boolean nullable Indica se é a moeda padrão da loja. Exemplo: false
     * @bodyParam status boolean nullable Indica se a moeda está ativa. Exemplo: true
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Moeda atualizada com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "nome": "Dólar Americano",
     *     "codigo": "USD",
     *     "simbolo": "$",
     *     "taxa_cambio": 5.5,
     *     "padrao": false,
     *     "status": true
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para editar esta moeda."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Moeda não encontrada."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Validação falhou.",
     *   "errors": {
     *     "nome": ["O campo nome é obrigatório", "O nome já está em uso nesta loja"],
     *     "codigo": ["O código já está em uso nesta loja"],
     *     "simbolo": ["O campo simbolo é obrigatório"],
     *     "taxa_cambio": ["O campo taxa_cambio deve ser numérico e não negativo"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar a moeda.",
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
            $moeda = Moeda::findOrFail($id);

            if ($moeda->loja_id !== $user->loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar esta moeda.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nome' => ['required', 'string', 'max:255', "unique:moedas,nome,{$id},id,loja_id,{$user->loja_id}"],
                'codigo' => ['required', 'string', 'max:255', "unique:moedas,codigo,{$id},id,loja_id,{$user->loja_id}"],
                'simbolo' => 'required|string|max:10',
                'taxa_cambio' => 'required|numeric|min:0',
                'padrao' => 'nullable|boolean',
                'status' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validação falhou.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $moeda->update($request->only([
                'nome', 'codigo', 'simbolo', 'taxa_cambio', 'padrao', 'status'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Moeda atualizada com sucesso!',
                'data' => $moeda
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Moeda não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar a moeda.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove uma moeda.
     *
     * Deleta uma moeda específica, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID da moeda.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Moeda removida com sucesso!"
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para remover esta moeda."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Moeda não encontrada."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover a moeda.",
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
            $moeda = Moeda::findOrFail($id);

            if ($moeda->loja_id !== $user->loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para remover esta moeda.'
                ], 403);
            }

            // TODO: Verify dependencies (e.g., orders or products using this currency) before deletion
            $moeda->delete();

            return response()->json([
                'success' => true,
                'message' => 'Moeda removida com sucesso!'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Moeda não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover a moeda.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
