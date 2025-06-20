<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\MarcaGt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * @group Gestão de Template - Marcas
 *
 * Endpoints para gerenciamento de marcas associadas a templates e lojas.
 */
class MarcaGtController extends Controller
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
     * Recupera o nome da pasta associada à loja.
     *
     * @return string|null O nome da pasta da loja ou null se não encontrado.
     * @throws \Exception Se a loja não for encontrada.
     */
    private function dadosLojaPasta(): ?string
    {
        $lojaId = $this->getLojaId();
        if (!$lojaId) {
            throw new \Exception('Loja não associada ao usuário.');
        }

        $dadosLoja = DB::table('lojas')->where('id', $lojaId)->first();
        if (!$dadosLoja) {
            throw new \Exception('Loja não encontrada.');
        }

        return $dadosLoja->pasta;
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
     * Faz o upload de um arquivo para o armazenamento.
     *
     * @param Request $request A requisição HTTP.
     * @param string $campo O nome do campo do arquivo na requisição.
     * @param string $pasta O diretório de destino no armazenamento.
     * @return string|null O caminho do arquivo armazenado ou null se não houver upload.
     * @throws \Exception Se o upload falhar ou a pasta da loja não for encontrada.
     */
    private function uploadArquivo(Request $request, string $campo, string $pasta): ?string
    {
        if (!$request->hasFile($campo)) {
            return null;
        }

        $file = $request->file($campo);
        $validator = Validator::make([$campo => $file], [
            $campo => 'file|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            throw new \Exception('Arquivo inválido: ' . $validator->errors()->first($campo));
        }

        $nomeOriginal = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extensao = $file->getClientOriginalExtension();
        $nomeUnico = Str::slug($nomeOriginal) . '-' . Str::random(10) . '.' . $extensao;

        $pastaLoja = $this->dadosLojaPasta();
        $caminho = "{$pastaLoja}/assets/gestaoTemplate/{$pasta}";
        Storage::disk('public')->makeDirectory($caminho);
        $arquivoPath = $file->storeAs($caminho, $nomeUnico, 'public');

        return $arquivoPath;
    }

    /**
     * Lista todas as marcas de um template.
     *
     * Retorna uma lista de marcas associadas ao template especificado e à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam template_id integer required O ID do template. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Marcas listadas com sucesso.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "loja_id": 1,
     *       "template_id": 1,
     *       "tipo_visualizacao": "Carrossel",
     *       "titulo": "Marca Exemplo",
     *       "imagem": "marca-exemplo.jpg",
     *       "imagem_path": "https://example.com/storage/loja_123/assets/gestaoTemplate/marcas_gt/marca-exemplo.jpg",
     *       "created_at": "2025-05-28T16:53:00.000000Z",
     *       "updated_at": "2025-05-28T16:53:00.000000Z"
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
     *   "message": "Erro ao listar marcas.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function index(int $template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $lojaId = $this->getLojaId();
            $marcas = MarcaGt::where('loja_id', $lojaId)
                ->where('template_id', $template_id)
                ->get();

            $marcas->transform(function ($marca) {
                if ($marca->imagem_path) {
                    $marca->imagem_path = url("storage/{$marca->imagem_path}");
                }
                return $marca;
            });

            return response()->json([
                'success' => true,
                'message' => 'Marcas listadas com sucesso.',
                'data' => $marcas
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar marcas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe uma marca específica.
     *
     * Retorna os detalhes de uma marca específica com base no ID do template e da marca.
     *
     * @authenticated
     * @urlParam template_id integer required O ID do template. Exemplo: 1
     * @urlParam id integer required O ID da marca. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Marca encontrada com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "template_id": 1,
     *     "tipo_visualizacao": "Carrossel",
     *     "titulo": "Marca Exemplo",
     *     "imagem": "marca-exemplo.jpg",
     *     "imagem_path": "https://example.com/storage/loja_123/assets/gestaoTemplate/marcas_gt/marca-exemplo.jpg",
     *     "created_at": "2025-05-28T16:53:00.000000Z",
     *     "updated_at": "2025-05-28T16:53:00.000000Z"
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
     *   "message": "Marca não encontrada."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar marca.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function show(int $template_id, int $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $lojaId = $this->getLojaId();
            $marca = MarcaGt::where('loja_id', $lojaId)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            if ($marca->imagem_path) {
                $marca->imagem_path = url("storage/{$marca->imagem_path}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Marca encontrada com sucesso.',
                'data' => $marca
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Marca não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar marca.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria uma nova marca.
     *
     * Cria uma nova marca associada ao template e à loja do usuário autenticado, com upload de imagem.
     *
     * @authenticated
     * @urlParam template_id integer required O ID do template. Exemplo: 1
     * @bodyParam tipo_visualizacao string required Tipo de visualização da marca (Carrossel, Grade, Lista). Exemplo: Carrossel
     * @bodyParam titulo string required Título da marca (máx. 255 caracteres). Exemplo: Marca Exemplo
     * @bodyParam imagem file required Imagem da marca (jpg, jpeg, png, webp, máx. 2MB).
     * @response 201 {
     *   "success": true,
     *   "message": "Marca criada com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "template_id": 1,
     *     "tipo_visualizacao": "Carrossel",
     *     "titulo": "Marca Exemplo",
     *     "imagem": "marca-exemplo.jpg",
     *     "imagem_path": "https://example.com/storage/loja_123/assets/gestaoTemplate/marcas_gt/marca-exemplo.jpg",
     *     "created_at": "2025-05-28T16:53:00.000000Z",
     *     "updated_at": "2025-05-28T16:53:00.000000Z"
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
     *     "tipo_visualizacao": ["O campo tipo_visualizacao deve ser um dos seguintes: Carrossel, Grade, Lista."],
     *     "titulo": ["O campo título é obrigatório."],
     *     "imagem": ["O campo imagem deve ser um arquivo do tipo: jpg, jpeg, png, webp."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar marca.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function store(Request $request, int $template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $lojaId = $this->getLojaId();
            $validator = Validator::make($request->all(), [
                'tipo_visualizacao' => 'required|string|in:Carrossel,Grade,Lista',
                'titulo' => 'required|string|max:255',
                'imagem' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $imagemPath = $this->uploadArquivo($request, 'imagem', 'marcas_gt');
            if (!$imagemPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Falha ao fazer upload da imagem.'
                ], 500);
            }

            $marca = MarcaGt::create([
                'loja_id' => $lojaId,
                'template_id' => $template_id,
                'tipo_visualizacao' => $request->tipo_visualizacao,
                'titulo' => $request->titulo,
                'imagem' => basename($imagemPath),
                'imagem_path' => $imagemPath,
            ]);

            $marca->imagem_path = url("storage/{$marca->imagem_path}");

            return response()->json([
                'success' => true,
                'message' => 'Marca criada com sucesso.',
                'data' => $marca
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar marca.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza uma marca existente.
     *
     * Atualiza os dados de uma marca específica, com a opção de substituir a imagem.
     *
     * @authenticated
     * @urlParam template_id integer required O ID do template. Exemplo: 1
     * @urlParam id integer required O ID da marca. Exemplo: 1
     * @bodyParam tipo_visualizacao string|null Tipo de visualização da marca (Carrossel, Grade, Lista). Exemplo: Grade
     * @bodyParam titulo string|null Título da marca (máx. 255 caracteres). Exemplo: Marca Atualizada
     * @bodyParam imagem file|null Nova imagem da marca (jpg, jpeg, png, webp, máx. 2MB).
     * @response 200 {
     *   "success": true,
     *   "message": "Marca atualizada com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "template_id": 1,
     *     "tipo_visualizacao": "Grade",
     *     "titulo": "Marca Atualizada",
     *     "imagem": "marca-atualizada.jpg",
     *     "imagem_path": "https://example.com/storage/loja_123/assets/gestaoTemplate/marcas_gt/marca-atualizada.jpg",
     *     "created_at": "2025-05-28T16:53:00.000000Z",
     *     "updated_at": "2025-05-28T16:53:00.000000Z"
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
     *   "message": "Marca não encontrada."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Erro de validação.",
     *   "errors": {
     *     "tipo_visualizacao": ["O campo tipo_visualizacao deve ser um dos seguintes: Carrossel, Grade, Lista."],
     *     "titulo": ["O campo título deve ser uma string."],
     *     "imagem": ["O campo imagem deve ser um arquivo do tipo: jpg, jpeg, png, webp."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar marca.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function update(Request $request, int $template_id, int $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $lojaId = $this->getLojaId();
            $marca = MarcaGt::where('loja_id', $lojaId)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'tipo_visualizacao' => 'sometimes|required|string|in:Carrossel,Grade,Lista',
                'titulo' => 'sometimes|required|string|max:255',
                'imagem' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dados = $request->only(['tipo_visualizacao', 'titulo']);

            if ($request->hasFile('imagem')) {
                if ($marca->imagem_path && Storage::disk('public')->exists($marca->imagem_path)) {
                    Storage::disk('public')->delete($marca->imagem_path);
                }
                $imagemPath = $this->uploadArquivo($request, 'imagem', 'marcas_gt');
                if ($imagemPath) {
                    $dados['imagem'] = basename($imagemPath);
                    $dados['imagem_path'] = $imagemPath;
                }
            }

            $marca->update($dados);
            $marca->imagem_path = $marca->imagem_path ? url("storage/{$marca->imagem_path}") : null;

            return response()->json([
                'success' => true,
                'message' => 'Marca atualizada com sucesso.',
                'data' => $marca
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Marca não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar marca.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deleta uma marca.
     *
     * Remove uma marca específica e sua imagem associada do armazenamento.
     *
     * @authenticated
     * @urlParam template_id integer required O ID do template. Exemplo: 1
     * @urlParam id integer required O ID da marca. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Marca deletada com sucesso."
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
     *   "message": "Marca não encontrada."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao deletar marca.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function destroy(int $template_id, int $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $lojaId = $this->getLojaId();
            $marca = MarcaGt::where('loja_id', $lojaId)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            if ($marca->imagem_path && Storage::disk('public')->exists($marca->imagem_path)) {
                Storage::disk('public')->delete($marca->imagem_path);
            }

            $marca->delete();

            return response()->json([
                'success' => true,
                'message' => 'Marca deletada com sucesso.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Marca não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar marca.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
