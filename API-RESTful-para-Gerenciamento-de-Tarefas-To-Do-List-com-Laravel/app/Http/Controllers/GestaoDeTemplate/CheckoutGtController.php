<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\CheckoutGt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * @group Gestão de Template - Checkouts
 *
 * Endpoints para gerenciamento de configurações de checkout associadas a templates e lojas.
 */
class CheckoutGtController extends Controller
{
    /**
     * Recupera o ID da loja do usuário autenticado.
     *
     * @return int|null O ID da loja ou null se não encontrado.
     */
    private function getLojaId(): ?int
    {
        $user = Auth::user();
        return $user->id_loja;
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
            return response()->json(['error' => 'Usuário não possui loja associada'], 403);
        }
        return null;
    }

    /**
     * Lista todas as configurações de checkout de um template.
     *
     * Retorna uma lista de configurações de checkout associadas ao template especificado e à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "loja_id": 1,
     *       "template_id": 1,
     *       "exibir_opcoes_entrega": true,
     *       "exibir_opcoes_pagamento": true,
     *       "exibir_resumo_pedido": false
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar checkouts"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $checkouts = CheckoutGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            return response()->json($checkouts, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar checkouts'], 500);
        }
    }

    /**
     * Exibe uma configuração de checkout específica.
     *
     * Retorna os detalhes de uma configuração de checkout específica com base no ID do template e do checkout.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do checkout.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "exibir_opcoes_entrega": true,
     *   "exibir_opcoes_pagamento": true,
     *   "exibir_resumo_pedido": false
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Checkout não encontrado"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $checkout = CheckoutGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            return response()->json($checkout, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Checkout não encontrado'], 404);
        }
    }

    /**
     * Cria uma nova configuração de checkout.
     *
     * Cria uma nova configuração de checkout associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam exibir_opcoes_entrega boolean required Define se as opções de entrega devem ser exibidas. Exemplo: true
     * @bodyParam exibir_opcoes_pagamento boolean required Define se as opções de pagamento devem ser exibidas. Exemplo: true
     * @bodyParam exibir_resumo_pedido boolean required Define se o resumo do pedido deve ser exibido. Exemplo: false
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "exibir_opcoes_entrega": true,
     *   "exibir_opcoes_pagamento": true,
     *   "exibir_resumo_pedido": false
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "exibir_opcoes_entrega": ["O campo exibir_opcoes_entrega é obrigatório"],
     *     "exibir_opcoes_pagamento": ["O campo exibir_opcoes_pagamento deve ser um booleano"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar checkout"
     * }
     */
    public function store(Request $request, $template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $request->validate([
                'exibir_opcoes_entrega' => 'required|boolean',
                'exibir_opcoes_pagamento' => 'required|boolean',
                'exibir_resumo_pedido' => 'required|boolean',
            ]);

            $checkout = CheckoutGt::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'exibir_opcoes_entrega' => $request->exibir_opcoes_entrega,
                'exibir_opcoes_pagamento' => $request->exibir_opcoes_pagamento,
                'exibir_resumo_pedido' => $request->exibir_resumo_pedido,
            ]);

            return response()->json($checkout, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar checkout'], 500);
        }
    }

    /**
     * Atualiza uma configuração de checkout existente.
     *
     * Atualiza os dados de uma configuração de checkout específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID do checkout.
     * @return JsonResponse
     *
     * @bodyParam exibir_opcoes_entrega boolean Define se as opções de entrega devem ser exibidas. Exemplo: true
     * @bodyParam exibir_opcoes_pagamento boolean Define se as opções de pagamento devem be exibidas. Exemplo: true
     * @bodyParam exibir_resumo_pedido boolean Define se o resumo do pedido deve ser exibido. Exemplo: false
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "exibir_opcoes_entrega": true,
     *   "exibir_opcoes_pagamento": true,
     *   "exibir_resumo_pedido": false
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Checkout não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "exibir_opcoes_entrega": ["O campo exibir_opcoes_entrega deve ser um booleano"],
     *     "exibir_opcoes_pagamento": ["O campo exibir_opcoes_pagamento é obrigatório"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar checkout"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $checkout = CheckoutGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'exibir_opcoes_entrega' => 'sometimes|required|boolean',
                'exibir_opcoes_pagamento' => 'sometimes|required|boolean',
                'exibir_resumo_pedido' => 'sometimes|required|boolean',
            ]);

            $checkout->update($request->only([
                'exibir_opcoes_entrega',
                'exibir_opcoes_pagamento',
                'exibir_resumo_pedido',
            ]));

            return response()->json($checkout, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar checkout'], 500);
        }
    }

    /**
     * Deleta uma configuração de checkout.
     *
     * Remove uma configuração de checkout específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do checkout.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Checkout deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Checkout não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar checkout"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $checkout = CheckoutGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $checkout->delete();

            return response()->json(['message' => 'Checkout deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar checkout'], 500);
        }
    }
}
