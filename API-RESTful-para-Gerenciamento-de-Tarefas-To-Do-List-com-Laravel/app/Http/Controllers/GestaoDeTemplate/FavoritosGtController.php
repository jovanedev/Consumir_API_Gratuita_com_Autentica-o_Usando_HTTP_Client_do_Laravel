<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\FavoritosGt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * @group Gestão de Template - Favoritos
 *
 * Endpoints para gerenciamento de configurações de favoritos associadas a templates e lojas.
 */
class FavoritosGtController extends Controller
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
     * Lista todas as configurações de favoritos de um template.
     *
     * Retorna uma lista de configurações de favoritos associadas ao template especificado e à loja do usuário autenticado.
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
     *       "favoritado": true
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar favoritos"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $favoritos = FavoritosGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            return response()->json($favoritos, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar favoritos'], 500);
        }
    }

    /**
     * Exibe uma configuração de favorito específica.
     *
     * Retorna os detalhes de uma configuração de favorito específica com base no ID do template e do favorito.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do favorito.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "favoritado": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Favorito não encontrado"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $favorito = FavoritosGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            return response()->json($favorito, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Favorito não encontrado'], 404);
        }
    }

    /**
     * Cria uma nova configuração de favorito.
     *
     * Cria uma nova configuração de favorito associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam favoritado boolean required Define se o item está favoritado. Exemplo: true
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "favoritado": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "favoritado": ["O campo favoritado é obrigatório"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar favorito"
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
                'favoritado' => 'required|boolean',
            ]);

            $favorito = FavoritosGt::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'favoritado' => $request->favoritado,
            ]);

            return response()->json($favorito, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar favorito'], 500);
        }
    }

    /**
     * Atualiza uma configuração de favorito existente.
     *
     * Atualiza os dados de uma configuração de favorito específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID do favorito.
     * @return JsonResponse
     *
     * @bodyParam favoritado boolean Define se o item está favoritado. Exemplo: true
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "favoritado": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Favorito não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "favoritado": ["O campo favoritado deve ser um booleano"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar favorito"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $favorito = FavoritosGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'favoritado' => 'sometimes|required|boolean',
            ]);

            $favorito->update($request->only([
                'favoritado',
            ]));

            return response()->json($favorito, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar favorito'], 500);
        }
    }

    /**
     * Deleta uma configuração de favorito.
     *
     * Remove uma configuração de favorito específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do favorito.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Favorito deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Favorito não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar favorito"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $favorito = FavoritosGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $favorito->delete();

            return response()->json(['message' => 'Favorito deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar favorito'], 500);
        }
    }
}
