<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\ProdutosNovos;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * @group Gestão de Produtos Novos
 *
 * Endpoints para gerenciamento de configurações de produtos novos associados a templates e lojas.
 */
class ProdutosNovosController extends Controller
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
     * Lista todos os produtos novos de um template.
     *
     * Retorna uma lista de configurações de produtos novos associados ao template e à loja do usuário autenticado.
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
     *       "titulo": "Novidades da Loja",
     *       "tipo_visualizacao": "Grade",
     *       "produtos_por_linha_celulares": 2,
     *       "produtos_por_linha_computadores": 4
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar produtos novos"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $produtosNovos = ProdutosNovos::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            return response()->json(['data' => $produtosNovos], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar produtos novos'], 500);
        }
    }

    /**
     * Exibe uma configuração de produto novo específica.
     *
     * Retorna os detalhes de uma configuração de produto novo com base no ID do template e da configuração.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID da configuração de produto novo.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Novidades da Loja",
     *   "tipo_visualizacao": "Grade",
     *   "produtos_por_linha_celulares": 2,
     *   "produtos_por_linha_computadores": 4
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Produto novo não encontrado"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $produtoNovo = ProdutosNovos::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            return response()->json($produtoNovo, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Produto novo não encontrado'], 404);
        }
    }

    /**
     * Cria uma nova configuração de produto novo.
     *
     * Cria uma nova configuração de produtos novos associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam titulo string required Título da seção de produtos novos (máx. 255 caracteres). Exemplo: Novidades da Loja
     * @bodyParam tipo_visualizacao string required Tipo de visualização (Grade ou Lista). Exemplo: Grade
     * @bodyParam produtos_por_linha_celulares integer required Número de produtos por linha em dispositivos móveis (1 a 10). Exemplo: 2
     * @bodyParam produtos_por_linha_computadores integer required Número de produtos por linha em computadores (1 a 10). Exemplo: 4
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Novidades da Loja",
     *   "tipo_visualizacao": "Grade",
     *   "produtos_por_linha_celulares": 2,
     *   "produtos_por_linha_computadores": 4
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "titulo": ["O campo título é obrigatório"],
     *     "tipo_visualizacao": ["O campo tipo_visualizacao deve ser Grade ou Lista"],
     *     "produtos_por_linha_celulares": ["O campo produtos_por_linha_celulares deve ser um número entre 1 e 10"],
     *     "produtos_por_linha_computadores": ["O campo produtos_por_linha_computadores deve ser um número entre 1 e 10"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar produto novo"
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
                'tipo_visualizacao' => 'required|string|in:Grade,Lista',
                'produtos_por_linha_celulares' => 'required|integer|min:1|max:10',
                'produtos_por_linha_computadores' => 'required|integer|min:1|max:10',
            ]);

            $produtoNovo = ProdutosNovos::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'titulo' => $request->titulo,
                'tipo_visualizacao' => $request->tipo_visualizacao,
                'produtos_por_linha_celulares' => $request->produtos_por_linha_celulares,
                'produtos_por_linha_computadores' => $request->produtos_por_linha_computadores,
            ]);

            return response()->json($produtoNovo, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar produto novo'], 500);
        }
    }

    /**
     * Atualiza uma configuração de produto novo existente.
     *
     * Atualiza os dados de uma configuração de produtos novos específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID da configuração de produto novo.
     * @return JsonResponse
     *
     * @bodyParam titulo string Título da seção de produtos novos (máx. 255 caracteres). Exemplo: Novidades Atualizadas
     * @bodyParam tipo_visualizacao string Tipo de visualização (Grade ou Lista). Exemplo: Lista
     * @bodyParam produtos_por_linha_celulares integer Número de produtos por linha em dispositivos móveis (1 a 10). Exemplo: 3
     * @bodyParam produtos_por_linha_computadores integer Número de produtos por linha em computadores (1 a 10). Exemplo: 5
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Novidades Atualizadas",
     *   "tipo_visualizacao": "Lista",
     *   "produtos_por_linha_celulares": 3,
     *   "produtos_por_linha_computadores": 5
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Produto novo não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "titulo": ["O campo título deve ser uma string"],
     *     "tipo_visualizacao": ["O campo tipo_visualizacao deve ser Grade ou Lista"],
     *     "produtos_por_linha_celulares": ["O campo produtos_por_linha_celulares deve ser um número entre 1 e 10"],
     *     "produtos_por_linha_computadores": ["O campo produtos_por_linha_computadores deve ser um número entre 1 e 10"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar produto novo"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $produtoNovo = ProdutosNovos::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'titulo' => 'sometimes|required|string|max:255',
                'tipo_visualizacao' => 'sometimes|required|string|in:Grade,Lista',
                'produtos_por_linha_celulares' => 'sometimes|required|integer|min:1|max:10',
                'produtos_por_linha_computadores' => 'sometimes|required|integer|min:1|max:10',
            ]);

            $produtoNovo->update($request->only([
                'titulo',
                'tipo_visualizacao',
                'produtos_por_linha_celulares',
                'produtos_por_linha_computadores',
            ]));

            return response()->json($produtoNovo, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar produto novo'], 500);
        }
    }

    /**
     * Deleta uma configuração de produto novo.
     *
     * Remove uma configuração de produto novo específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID da configuração de produto novo.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Produto novo deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Produto novo não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar produto novo"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $produtoNovo = ProdutosNovos::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $produtoNovo->delete();

            return response()->json(['message' => 'Produto novo deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar produto novo'], 500);
        }
    }
}
