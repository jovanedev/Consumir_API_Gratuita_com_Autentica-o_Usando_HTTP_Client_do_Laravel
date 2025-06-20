<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\ProdutosEmOferta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * @group Gestão de Template - Produtos em Oferta
 *
 * Endpoints para gerenciamento de configurações de produtos em oferta associados a templates e lojas.
 */
class ProdutosEmOfertaController extends Controller
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
     * Lista todos os produtos em oferta de um template.
     *
     * Retorna uma lista de configurações de produtos em oferta associados ao template e à loja do usuário autenticado.
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
     *       "titulo": "Ofertas Especiais",
     *       "tipo_visualizacao": "Carrossel",
     *       "produtos_por_linha_celulares": 2,
     *       "produtos_por_linha_computadores": 4
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar produtos em oferta"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $produtosOferta = ProdutosEmOferta::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            return response()->json(['data' => $produtosOferta], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar produtos em oferta'], 500);
        }
    }

    /**
     * Exibe uma configuração de produto em oferta específica.
     *
     * Retorna os detalhes de uma configuração de produto em oferta com base no ID do template e da configuração.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID da configuração de produto em oferta.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Ofertas Especiais",
     *   "tipo_visualizacao": "Carrossel",
     *   "produtos_por_linha_celulares": 2,
     *   "produtos_por_linha_computadores": 4
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Produto em oferta não encontrado"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $produtoOferta = ProdutosEmOferta::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            return response()->json($produtoOferta, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Produto em oferta não encontrado'], 404);
        }
    }

    /**
     * Cria uma nova configuração de produto em oferta.
     *
     * Cria uma nova configuração de produtos em oferta associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam titulo string required Título da seção de produtos em oferta (máx. 255 caracteres). Exemplo: Ofertas Especiais
     * @bodyParam tipo_visualizacao string required Tipo de visualização (Carrossel, Grade ou Lista). Exemplo: Carrossel
     * @bodyParam produtos_por_linha_celulares integer required Número de produtos por linha em dispositivos móveis (1 a 10). Exemplo: 2
     * @bodyParam produtos_por_linha_computadores integer required Número de produtos por linha em computadores (1 a 10). Exemplo: 4
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Ofertas Especiais",
     *   "tipo_visualizacao": "Carrossel",
     *   "produtos_por_linha_celulares": 2,
     *   "produtos_por_linha_computadores": 4
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "titulo": ["O campo título é obrigatório"],
     *     "tipo_visualizacao": ["O campo tipo_visualizacao deve ser Carrossel, Grade ou Lista"],
     *     "produtos_por_linha_celulares": ["O campo produtos_por_linha_celulares deve ser um número entre 1 e 10"],
     *     "produtos_por_linha_computadores": ["O campo produtos_por_linha_computadores deve ser um número entre 1 e 10"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar produto em oferta"
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
                'titulo' => 'required|string|max:255',
                'tipo_visualizacao' => 'required|string|in:Carrossel,Grade,Lista',
                'produtos_por_linha_celulares' => 'required|integer|min:1|max:10',
                'produtos_por_linha_computadores' => 'required|integer|min:1|max:10',
            ]);

            $produtoOferta = ProdutosEmOferta::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'titulo' => $request->titulo,
                'tipo_visualizacao' => $request->tipo_visualizacao,
                'produtos_por_linha_celulares' => $request->produtos_por_linha_celulares,
                'produtos_por_linha_computadores' => $request->produtos_por_linha_computadores,
            ]);

            return response()->json($produtoOferta, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar produto em oferta'], 500);
        }
    }

    /**
     * Atualiza uma configuração de produto em oferta existente.
     *
     * Atualiza os dados de uma configuração de produtos em oferta específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID da configuração de produto em oferta.
     * @return JsonResponse
     *
     * @bodyParam titulo string Título da seção de produtos em oferta (máx. 255 caracteres). Exemplo: Ofertas Atualizadas
     * @bodyParam tipo_visualizacao string Tipo de visualização (Carrossel, Grade ou Lista). Exemplo: Grade
     * @bodyParam produtos_por_linha_celulares integer Número de produtos por linha em dispositivos móveis (1 a 10). Exemplo: 3
     * @bodyParam produtos_por_linha_computadores integer Número de produtos por linha em computadores (1 a 10). Exemplo: 5
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Ofertas Atualizadas",
     *   "tipo_visualizacao": "Grade",
     *   "produtos_por_linha_celulares": 3,
     *   "produtos_por_linha_computadores": 5
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Produto em oferta não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "titulo": ["O campo título deve ser uma string"],
     *     "tipo_visualizacao": ["O campo tipo_visualizacao deve ser Carrossel, Grade ou Lista"],
     *     "produtos_por_linha_celulares": ["O campo produtos_por_linha_celulares deve ser um número entre 1 e 10"],
     *     "produtos_por_linha_computadores": ["O campo produtos_por_linha_computadores deve ser um número entre 1 e 10"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar produto em oferta"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $produtoOferta = ProdutosEmOferta::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'titulo' => 'sometimes|required|string|max:255',
                'tipo_visualizacao' => 'sometimes|required|string|in:Carrossel,Grade,Lista',
                'produtos_por_linha_celulares' => 'sometimes|required|integer|min:1|max:10',
                'produtos_por_linha_computadores' => 'sometimes|required|integer|min:1|max:10',
            ]);

            $produtoOferta->update($request->only([
                'titulo',
                'tipo_visualizacao',
                'produtos_por_linha_celulares',
                'produtos_por_linha_computadores',
            ]));

            return response()->json($produtoOferta, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar produto em oferta'], 500);
        }
    }

    /**
     * Deleta uma configuração de produto em oferta.
     *
     * Remove uma configuração de produto em oferta específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID da configuração de produto em oferta.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Produto em oferta deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Produto em oferta não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar produto em oferta"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $produtoOferta = ProdutosEmOferta::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $produtoOferta->delete();

            return response()->json(['message' => 'Produto em oferta deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar produto em oferta'], 500);
        }
    }
}
