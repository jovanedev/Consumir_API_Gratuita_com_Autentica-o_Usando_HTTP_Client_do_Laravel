<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\ProdutosEmDestaque;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * @group Gestão de Template - Produtos em Destaque
 *
 * Endpoints para gerenciamento de configurações de produtos em destaque associados a templates e lojas.
 */
class ProdutosEmDestaqueController extends Controller
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
     * Valida se o usuário possui uma loja associada e se corresponde ao loja_id fornecido.
     *
     * @param int $loja_id O ID da loja fornecido na rota.
     * @return JsonResponse|null Resposta JSON com erro 403 se não houver loja associada ou se não corresponder, ou null se válido.
     */
    private function validateLojaId(int $loja_id): ?JsonResponse
    {
        $userLojaId = $this->getLojaId();
        if (!$userLojaId || $userLojaId !== $loja_id) {
            return response()->json(['error' => 'Acesso não autorizado para esta loja'], 403);
        }
        return null;
    }

    /**
     * Lista todos os produtos em destaque de um template.
     *
     * Retorna uma lista de configurações de produtos em destaque associados ao template e loja especificados.
     *
     * @authenticated
     * @param int $loja_id O ID da loja.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "loja_id": 1,
     *       "template_id": 1,
     *       "titulo": "Produtos em Destaque",
     *       "tipo_visualizacao": "Grade",
     *       "produtos_por_linha_celulares": 2,
     *       "produtos_por_linha_computadores": 4
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Acesso não autorizado para esta loja"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar produtos em destaque"
     * }
     */
    public function index($loja_id, $template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId($loja_id)) {
                return $errorResponse;
            }

            $produtosDestaque = ProdutosEmDestaque::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            return response()->json($produtosDestaque, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar produtos em destaque'], 500);
        }
    }

    /**
     * Exibe uma configuração de produto em destaque específica.
     *
     * Retorna os detalhes de uma configuração de produto em destaque com base no ID do template, loja e configuração.
     *
     * @authenticated
     * @param int $loja_id O ID da loja.
     * @param int $template_id O ID do template.
     * @param int $id O ID da configuração de produto em destaque.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Produtos em Destaque",
     *   "tipo_visualizacao": "Grade",
     *   "produtos_por_linha_celulares": 2,
     *   "produtos_por_linha_computadores": 4
     * }
     * @responseError 403 {
     *   "error": "Acesso não autorizado para esta loja"
     * }
     * @responseError 404 {
     *   "error": "Produto em destaque não encontrado"
     * }
     */
    public function show($loja_id, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId($loja_id)) {
                return $errorResponse;
            }

            $produtoDestaque = ProdutosEmDestaque::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            return response()->json($produtoDestaque, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Produto em destaque não encontrado'], 404);
        }
    }

    /**
     * Cria uma nova configuração de produto em destaque.
     *
     * Cria uma nova configuração de produtos em destaque associada ao template e loja especificados.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $loja_id O ID da loja.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam titulo string required Título da seção de produtos em destaque (máx. 255 caracteres). Exemplo: Produtos em Destaque
     * @bodyParam tipo_visualizacao string required Tipo de visualização (Grade ou Lista). Exemplo: Grade
     * @bodyParam produtos_por_linha_celulares integer required Número de produtos por linha em dispositivos móveis (1 a 10). Exemplo: 2
     * @bodyParam produtos_por_linha_computadores integer required Número de produtos por linha em computadores (1 a 10). Exemplo: 4
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Produtos em Destaque",
     *   "tipo_visualizacao": "Grade",
     *   "produtos_por_linha_celulares": 2,
     *   "produtos_por_linha_computadores": 4
     * }
     * @responseError 403 {
     *   "error": "Acesso não autorizado para esta loja"
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
     *   "error": "Erro ao criar produto em destaque"
     * }
     */
    public function store(Request $request, $loja_id, $template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId($loja_id)) {
                return $errorResponse;
            }

            $request->validate([
                'titulo' => 'required|string|max:255',
                'tipo_visualizacao' => 'required|string|in:Grade,Lista',
                'produtos_por_linha_celulares' => 'required|integer|min:1|max:10',
                'produtos_por_linha_computadores' => 'required|integer|min:1|max:10',
            ]);

            $produtoDestaque = ProdutosEmDestaque::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'titulo' => $request->titulo,
                'tipo_visualizacao' => $request->tipo_visualizacao,
                'produtos_por_linha_celulares' => $request->produtos_por_linha_celulares,
                'produtos_por_linha_computadores' => $request->produtos_por_linha_computadores,
            ]);

            return response()->json($produtoDestaque, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar produto em destaque'], 500);
        }
    }

    /**
     * Atualiza uma configuração de produto em destaque existente.
     *
     * Atualiza os dados de uma configuração de produtos em destaque específica associada ao template e loja especificados.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $loja_id O ID da loja.
     * @param int $template_id O ID do template.
     * @param int $id O ID da configuração de produto em destaque.
     * @return JsonResponse
     *
     * @bodyParam titulo string Título da seção de produtos em destaque (máx. 255 caracteres). Exemplo: Produtos em Destaque Atualizados
     * @bodyParam tipo_visualizacao string Tipo de visualização (Grade ou Lista). Exemplo: Lista
     * @bodyParam produtos_por_linha_celulares integer Número de produtos por linha em dispositivos móveis (1 a 10). Exemplo: 3
     * @bodyParam produtos_por_linha_computadores integer Número de produtos por linha em computadores (1 a 10). Exemplo: 5
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Produtos em Destaque Atualizados",
     *   "tipo_visualizacao": "Lista",
     *   "produtos_por_linha_celulares": 3,
     *   "produtos_por_linha_computadores": 5
     * }
     * @responseError 403 {
     *   "error": "Acesso não autorizado para esta loja"
     * }
     * @responseError 404 {
     *   "error": "Produto em destaque não encontrado"
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
     *   "error": "Erro ao atualizar produto em destaque"
     * }
     */
    public function update(Request $request, $loja_id, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId($loja_id)) {
                return $errorResponse;
            }

            $produtoDestaque = ProdutosEmDestaque::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'titulo' => 'sometimes|required|string|max:255',
                'tipo_visualizacao' => 'sometimes|required|string|in:Grade,Lista',
                'produtos_por_linha_celulares' => 'sometimes|required|integer|min:1|max:10',
                'produtos_por_linha_computadores' => 'sometimes|required|integer|min:1|max:10',
            ]);

            $produtoDestaque->update($request->only([
                'titulo',
                'tipo_visualizacao',
                'produtos_por_linha_celulares',
                'produtos_por_linha_computadores',
            ]));

            return response()->json($produtoDestaque, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar produto em destaque'], 500);
        }
    }

    /**
     * Deleta uma configuração de produto em destaque.
     *
     * Remove uma configuração de produto em destaque específica associada ao template e loja especificados.
     *
     * @authenticated
     * @param int $loja_id O ID da loja.
     * @param int $template_id O ID do template.
     * @param int $id O ID da configuração de produto em destaque.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Produto em destaque deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Acesso não autorizado para esta loja"
     * }
     * @responseError 404 {
     *   "error": "Produto em destaque não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar produto em destaque"
     * }
     */
    public function destroy($loja_id, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId($loja_id)) {
                return $errorResponse;
            }

            $produtoDestaque = ProdutosEmDestaque::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $produtoDestaque->delete();

            return response()->json(['message' => 'Produto em destaque deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar produto em destaque'], 500);
        }
    }
}
