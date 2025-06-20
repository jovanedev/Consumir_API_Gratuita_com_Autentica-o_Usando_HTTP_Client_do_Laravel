<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\TextosGt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * @group Gestão de Template - Textos
 *
 * Endpoints para gerenciamento de textos associados a templates e lojas.
 */
class TextosGtController extends Controller
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
     * Lista todos os textos de um template.
     *
     * Retorna uma lista de textos associados ao template e à loja do usuário autenticado.
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
     *       "titulo": "Bem-vindo à Loja",
     *       "conteudo": "Explore nossa nova coleção de produtos!",
     *       "tipo_texto": "Cabecalho"
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar textos"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $textos = TextosGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            return response()->json(['data' => $textos], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar textos'], 500);
        }
    }

    /**
     * Exibe um texto específico.
     *
     * Retorna os detalhes de um texto específico com base no ID do template e do texto.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do texto.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Bem-vindo à Loja",
     *   "conteudo": "Explore nossa nova coleção de produtos!",
     *   "tipo_texto": "Cabecalho"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Texto não encontrado"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $texto = TextosGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            return response()->json($texto, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Texto não encontrado'], 404);
        }
    }

    /**
     * Cria um novo texto.
     *
     * Cria um novo texto associado ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam titulo string required Título do texto (máx. 255 caracteres). Exemplo: Bem-vindo à Loja
     * @bodyParam conteudo string required Conteúdo do texto (máx. 1000 caracteres). Exemplo: Explore nossa nova coleção de produtos!
     * @bodyParam tipo_texto string required Tipo do texto (Cabecalho, Rodape, Banner, Titulo, Descricao). Exemplo: Cabecalho
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Bem-vindo à Loja",
     *   "conteudo": "Explore nossa nova coleção de produtos!",
     *   "tipo_texto": "Cabecalho"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "titulo": ["O campo título é obrigatório"],
     *     "conteudo": ["O campo conteúdo é obrigatório"],
     *     "tipo_texto": ["O campo tipo_texto deve ser Cabecalho, Rodape, Banner, Titulo ou Descricao"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar texto"
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
                'conteudo' => 'required|string|max:1000',
                'tipo_texto' => 'required|string|in:Cabecalho,Rodape,Banner,Titulo,Descricao',
            ]);

            $texto = TextosGt::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'titulo' => $request->titulo,
                'conteudo' => $request->conteudo,
                'tipo_texto' => $request->tipo_texto,
            ]);

            return response()->json($texto, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar texto'], 500);
        }
    }

    /**
     * Atualiza um texto existente.
     *
     * Atualiza os dados de um texto específico associado ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID do texto.
     * @return JsonResponse
     *
     * @bodyParam titulo string Título do texto (máx. 255 caracteres). Exemplo: Bem-vindo Atualizado
     * @bodyParam conteudo string Conteúdo do texto (máx. 1000 caracteres). Exemplo: Confira nossas novas ofertas!
     * @bodyParam tipo_texto string Tipo do texto (Cabecalho, Rodape, Banner, Titulo, Descricao). Exemplo: Banner
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Bem-vindo Atualizado",
     *   "conteudo": "Confira nossas novas ofertas!",
     *   "tipo_texto": "Banner"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Texto não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "titulo": ["O campo título deve ser uma string"],
     *     "conteudo": ["O campo conteúdo deve ser uma string"],
     *     "tipo_texto": ["O campo tipo_texto deve ser Cabecalho, Rodape, Banner, Titulo ou Descricao"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar texto"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $texto = TextosGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'titulo' => 'sometimes|required|string|max:255',
                'conteudo' => 'sometimes|required|string|max:1000',
                'tipo_texto' => 'sometimes|required|string|in:Cabecalho,Rodape,Banner,Titulo,Descricao',
            ]);

            $texto->update($request->only([
                'titulo',
                'conteudo',
                'tipo_texto',
            ]));

            return response()->json($texto, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar texto'], 500);
        }
    }

    /**
     * Deleta um texto.
     *
     * Remove um texto específico associado ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do texto.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Texto deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Texto não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar texto"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $texto = TextosGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $texto->delete();

            return response()->json(['message' => 'Texto deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar texto'], 500);
        }
    }
}
