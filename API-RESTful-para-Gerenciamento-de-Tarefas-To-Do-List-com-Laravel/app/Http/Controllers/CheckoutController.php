<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group Checkouts
 *
 * Endpoints para gerenciamento de checkouts associados a uma loja.
 */
class CheckoutController extends Controller
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
     * Cria um novo checkout.
     *
     * Cria um checkout associado à loja do usuário autenticado.
     *
     * @authenticated
     * @bodyParam cores_layout boolean|null Define se o layout de cores está ativado (padrão: false). Exemplo: true
     * @bodyParam pedir_telefone boolean|null Define se o telefone é solicitado (padrão: false). Exemplo: true
     * @bodyParam pedir_endereco boolean|null Define se o endereço é solicitado (padrão: false). Exemplo: true
     * @bodyParam mensagem_cliente string|null Mensagem personalizada para o cliente. Exemplo: "Obrigado pela compra!"
     * @bodyParam mensagem_segmento string|null Mensagem para o segmento. Exemplo: "Segmento de varejo"
     * @bodyParam compra string|null Detalhes da compra. Exemplo: "Compra online"
     * @bodyParam checkout_acelerado boolean|null Define se o checkout acelerado está ativado (padrão: false). Exemplo: true
     * @response 201 {
     *   "success": true,
     *   "message": "Checkout criado com sucesso.",
     *   "checkout_id": 1,
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "cores_layout": true,
     *     "pedir_telefone": true,
     *     "pedir_endereco": true,
     *     "mensagem_cliente": "Obrigado pela compra!",
     *     "mensagem_segmento": "Segmento de varejo",
     *     "compra": "Compra online",
     *     "checkout_acelerado": true,
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
     *     "cores_layout": ["O campo cores_layout deve ser um booleano."],
     *     "pedir_telefone": ["O campo pedir_telefone deve ser um booleano."],
     *     "pedir_endereco": ["O campo pedir_endereco deve ser um booleano."],
     *     "mensagem_cliente": ["O campo mensagem_cliente deve ser uma string."],
     *     "mensagem_segmento": ["O campo mensagem_segmento deve ser uma string."],
     *     "compra": ["O campo compra deve ser uma string."],
     *     "checkout_acelerado": ["O campo checkout_acelerado deve ser um booleano."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar checkout.",
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
                'cores_layout' => 'nullable|boolean',
                'pedir_telefone' => 'nullable|boolean',
                'pedir_endereco' => 'nullable|boolean',
                'mensagem_cliente' => 'nullable|string',
                'mensagem_segmento' => 'nullable|string',
                'compra' => 'nullable|string',
                'checkout_acelerado' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $checkout = Checkout::create([
                'loja_id' => $lojaId,
                'cores_layout' => $request->cores_layout ?? false,
                'pedir_telefone' => $request->pedir_telefone ?? false,
                'pedir_endereco' => $request->pedir_endereco ?? false,
                'mensagem_cliente' => $request->mensagem_cliente,
                'mensagem_segmento' => $request->mensagem_segmento,
                'compra' => $request->compra,
                'checkout_acelerado' => $request->checkout_acelerado ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Checkout criado com sucesso.',
                'checkout_id' => $checkout->id,
                'data' => $checkout
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar checkout.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe os detalhes de um checkout.
     *
     * Retorna os detalhes de um checkout com base no ID, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID do checkout. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Checkout encontrado com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "cores_layout": true,
     *     "pedir_telefone": true,
     *     "pedir_endereco": true,
     *     "mensagem_cliente": "Obrigado pela compra!",
     *     "mensagem_segmento": "Segmento de varejo",
     *     "compra": "Compra online",
     *     "checkout_acelerado": true,
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
     *   "message": "Checkout não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar checkout.",
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

            $checkout = Checkout::where('loja_id', $lojaId)->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Checkout encontrado com sucesso.',
                'data' => $checkout
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar checkout.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza um checkout existente.
     *
     * Atualiza os detalhes de um checkout específico, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID do checkout. Exemplo: 1
     * @bodyParam cores_layout boolean|null Define se o layout de cores está ativado. Exemplo: false
     * @bodyParam pedir_telefone boolean|null Define se o telefone é solicitado. Exemplo: false
     * @bodyParam pedir_endereco boolean|null Define se o endereço é solicitado. Exemplo: false
     * @bodyParam mensagem_cliente string|null Mensagem personalizada para o cliente. Exemplo: "Nova mensagem"
     * @bodyParam mensagem_segmento string|null Mensagem para o segmento. Exemplo: "Novo segmento"
     * @bodyParam compra string|null Detalhes da compra. Exemplo: "Compra atualizada"
     * @bodyParam checkout_acelerado boolean|null Define se o checkout acelerado está ativado. Exemplo: false
     * @response 200 {
     *   "success": true,
     *   "message": "Checkout atualizado com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "cores_layout": false,
     *     "pedir_telefone": false,
     *     "pedir_endereco": false,
     *     "mensagem_cliente": "Nova mensagem",
     *     "mensagem_segmento": "Novo segmento",
     *     "compra": "Compra atualizada",
     *     "checkout_acelerado": false,
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
     *   "message": "Checkout não encontrado."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Erro de validação.",
     *   "errors": {
     *     "cores_layout": ["O campo cores_layout deve ser um booleano."],
     *     "pedir_telefone": ["O campo pedir_telefone deve ser um booleano."],
     *     "pedir_endereco": ["O campo pedir_endereco deve ser um booleano."],
     *     "mensagem_cliente": ["O campo mensagem_cliente deve ser uma string."],
     *     "mensagem_segmento": ["O campo mensagem_segmento deve ser uma string."],
     *     "compra": ["O campo compra deve ser uma string."],
     *     "checkout_acelerado": ["O campo checkout_acelerado deve ser um booleano."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar checkout.",
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

            $checkout = Checkout::where('loja_id', $lojaId)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'cores_layout' => 'nullable|boolean',
                'pedir_telefone' => 'nullable|boolean',
                'pedir_endereco' => 'nullable|boolean',
                'mensagem_cliente' => 'nullable|string',
                'mensagem_segmento' => 'nullable|string',
                'compra' => 'nullable|string',
                'checkout_acelerado' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $checkout->update([
                'cores_layout' => $request->cores_layout ?? $checkout->cores_layout,
                'pedir_telefone' => $request->pedir_telefone ?? $checkout->pedir_telefone,
                'pedir_endereco' => $request->pedir_endereco ?? $checkout->pedir_endereco,
                'mensagem_cliente' => $request->mensagem_cliente ?? $checkout->mensagem_cliente,
                'mensagem_segmento' => $request->mensagem_segmento ?? $checkout->mensagem_segmento,
                'compra' => $request->compra ?? $checkout->compra,
                'checkout_acelerado' => $request->checkout_acelerado ?? $checkout->checkout_acelerado,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Checkout atualizado com sucesso.',
                'data' => $checkout
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar checkout.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove um checkout.
     *
     * Remove um checkout específico, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID do checkout. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Checkout removido com sucesso."
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
     *   "message": "Checkout não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover checkout.",
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

            $checkout = Checkout::where('loja_id', $lojaId)->findOrFail($id);

            $checkout->delete();

            return response()->json([
                'success' => true,
                'message' => 'Checkout removido com sucesso.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover checkout.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
