<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\CarrinhoGt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * @group Gestão de Template - Carrinhos
 *
 * Endpoints para gerenciamento de configurações de carrinhos associados a templates e lojas.
 */
class CarrinhoGtController extends Controller
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
     * Lista todas as configurações de carrinhos de um template.
     *
     * Retorna uma lista de configurações de carrinhos associadas ao template especificado e à loja do usuário autenticado.
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
     *       "mostrar_botao_ver_mais": true,
     *       "valor_minimo_compra": 50.00,
     *       "carrinho_rapido": false,
     *       "sugerir_produtos_complementares": true,
     *       "mostrar_calculadora_frete": true
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar carrinhos"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();
            $carrinhos = CarrinhoGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            return response()->json($carrinhos, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar carrinhos'], 500);
        }
    }

    /**
     * Exibe uma configuração de carrinho específica.
     *
     * Retorna os detalhes de uma configuração de carrinho específica com base no ID do template e do carrinho.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do carrinho.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "mostrar_botao_ver_mais": true,
     *   "valor_minimo_compra": 50.00,
     *   "carrinho_rapido": false,
     *   "sugerir_produtos_complementares": true,
     *   "mostrar_calculadora_frete": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Carrinho não encontrado"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();
            $carrinho = CarrinhoGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            return response()->json($carrinho, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Carrinho não encontrado'], 404);
        }
    }

    /**
     * Cria uma nova configuração de carrinho.
     *
     * Cria uma nova configuração de carrinho associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param Request $request A requisição HTTP.
     * @return JsonResponse
     *
     * @bodyParam mostrar_botao_ver_mais boolean required Define se o botão "Ver Mais" deve ser exibido. Exemplo: true
     * @bodyParam valor_minimo_compra numeric required O valor mínimo para compra (mín. 0). Exemplo: 50.00
     * @bodyParam carrinho_rapido boolean required Define se o carrinho rápido está ativado. Exemplo: false
     * @bodyParam sugerir_produtos_complementares boolean required Define se produtos complementares devem ser sugeridos. Exemplo: true
     * @bodyParam mostrar_calculadora_frete boolean required Define se a calculadora de frete deve ser exibida. Exemplo: true
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "mostrar_botao_ver_mais": true,
     *   "valor_minimo_compra": 50.00,
     *   "carrinho_rapido": false,
     *   "sugerir_produtos_complementares": true,
     *   "mostrar_calculadora_frete": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "mostrar_botao_ver_mais": ["O campo mostrar_botao_ver_mais é obrigatório"],
     *     "valor_minimo_compra": ["O campo valor_minimo_compra deve ser um número maior ou igual a 0"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar carrinho"
     * }
     */
    public function store($template_id, Request $request): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();

            $request->validate([
                'mostrar_botao_ver_mais' => 'required|boolean',
                'valor_minimo_compra' => 'required|numeric|min:0',
                'carrinho_rapido' => 'required|boolean',
                'sugerir_produtos_complementares' => 'required|boolean',
                'mostrar_calculadora_frete' => 'required|boolean',
            ]);

            $carrinho = CarrinhoGt::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'mostrar_botao_ver_mais' => $request->mostrar_botao_ver_mais,
                'valor_minimo_compra' => $request->valor_minimo_compra,
                'carrinho_rapido' => $request->carrinho_rapido,
                'sugerir_produtos_complementares' => $request->sugerir_produtos_complementares,
                'mostrar_calculadora_frete' => $request->mostrar_calculadora_frete,
            ]);

            return response()->json($carrinho, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar carrinho'], 500);
        }
    }

    /**
     * Atualiza uma configuração de carrinho existente.
     *
     * Atualiza os dados de uma configuração de carrinho específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID do carrinho.
     * @return JsonResponse
     *
     * @bodyParam mostrar_botao_ver_mais boolean Define se o botão "Ver Mais" deve ser exibido. Exemplo: true
     * @bodyParam valor_minimo_compra numeric O valor mínimo para compra (mín. 0). Exemplo: 50.00
     * @bodyParam carrinho_rapido boolean Define se o carrinho rápido está ativado. Exemplo: false
     * @bodyParam sugerir_produtos_complementares boolean Define se produtos complementares devem ser sugeridos. Exemplo: true
     * @bodyParam mostrar_calculadora_frete boolean Define se a calculadora de frete deve ser exibida. Exemplo: true
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "mostrar_botao_ver_mais": true,
     *   "valor_minimo_compra": 50.00,
     *   "carrinho_rapido": false,
     *   "sugerir_produtos_complementares": true,
     *   "mostrar_calculadora_frete": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Carrinho não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "mostrar_botao_ver_mais": ["O campo mostrar_botao_ver_mais deve ser um booleano"],
     *     "valor_minimo_compra": ["O campo valor_minimo_compra deve ser um número maior ou igual a 0"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar carrinho"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();

            $carrinho = CarrinhoGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'mostrar_botao_ver_mais' => 'sometimes|required|boolean',
                'valor_minimo_compra' => 'sometimes|required|numeric|min:0',
                'carrinho_rapido' => 'sometimes|required|boolean',
                'sugerir_produtos_complementares' => 'sometimes|required|boolean',
                'mostrar_calculadora_frete' => 'sometimes|required|boolean',
            ]);

            $carrinho->update($request->only([
                'mostrar_botao_ver_mais',
                'valor_minimo_compra',
                'carrinho_rapido',
                'sugerir_produtos_complementares',
                'mostrar_calculadora_frete',
            ]));

            return response()->json($carrinho, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar carrinho'], 500);
        }
    }

    /**
     * Deleta uma configuração de carrinho.
     *
     * Remove uma configuração de carrinho específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do carrinho.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Carrinho deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Carrinho não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar carrinho"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();

            $carrinho = CarrinhoGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $carrinho->delete();

            return response()->json(['message' => 'Carrinho deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar carrinho'], 500);
        }
    }
}
