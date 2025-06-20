<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\ImagensGt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

/**
 * @group Gestão de Template - Imagens
 *
 * Endpoints para gerenciamento de imagens associadas a templates e lojas.
 */
class ImagensGtController extends Controller
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
     * Recupera o nome da pasta associada à loja.
     *
     * @return string|null O nome da pasta da loja ou null se não encontrado.
     */
    private function dadosLojaPasta(): ?string
    {
        $dados_loja = DB::table('lojas')->where('id', $loja_id)->first();
        return $dados_loja->pasta;
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
     * Faz o upload de um arquivo para o armazenamento.
     *
     * @param Request $request A requisição HTTP.
     * @param string $campo O nome do campo do arquivo na requisição.
     * @param string $pasta O diretório de destino no armazenamento.
     * @return string|null O caminho do arquivo armazenado ou null se não houver upload.
     */
    private function uploadArquivo(Request $request, string $campo, string $pasta): ?string
    {
        if ($request->hasFile($campo)) {
            $file = $request->file($campo);
            $nomeOriginal = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extensao = $file->getClientOriginalExtension();
            $nomeUnico = Str::slug($nomeOriginal) . '-' . Str::random(10) . '.' . $extensao;

            $pastaloja = $this->dadosLojaPasta();
            $caminho = $pastaloja . "/assets/gestaoTemplate/{$pasta}";
            $arquivoPath = $file->storeAs($caminho, $nomeUnico, 'public');

            return $arquivoPath;
        }

        return null;
    }

    /**
     * Lista todas as imagens de um template.
     *
     * Retorna uma lista de imagens associadas ao template especificado e à loja do usuário autenticado.
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
     *       "imagem": "imagem-exemplo.jpg",
     *       "imagem_path": "https://storage.exemplo.com/path/to/imagem-exemplo.jpg",
     *       "titulo": "Imagem Exemplo"
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar imagens"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $imagens = ImagensGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            $imagens->transform(function ($imagem) {
                if ($imagem->imagem_path) {
                    $imagem->imagem_path = url("storage/{$imagem->imagem_path}");
                }
                return $imagem;
            });

            return response()->json($imagens, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar imagens'], 500);
        }
    }

    /**
     * Exibe uma imagem específica.
     *
     * Retorna os detalhes de uma imagem específica com base no ID do template e da imagem.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID da imagem.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "imagem": "imagem-exemplo.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/imagem-exemplo.jpg",
     *   "titulo": "Imagem Exemplo"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Imagem não encontrada"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $imagem = ImagensGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            if ($imagem->imagem_path) {
                $imagem->imagem_path = url("storage/{$imagem->imagem_path}");
            }

            return response()->json($imagem, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Imagem não encontrada'], 404);
        }
    }

    /**
     * Cria uma nova imagem.
     *
     * Cria uma nova imagem associada ao template e à loja do usuário autenticado, com upload de arquivo.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam imagem file required A imagem a ser carregada (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam titulo string nullable O título da imagem (máx. 255 caracteres). Exemplo: "Imagem Exemplo"
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "imagem": "imagem-exemplo.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/imagem-exemplo.jpg",
     *   "titulo": "Imagem Exemplo"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "imagem": ["O campo imagem é obrigatório", "O campo imagem deve ser um arquivo do tipo: jpg, jpeg, png, webp"],
     *     "titulo": ["O campo título deve ser uma string"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar imagem"
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
                'imagem' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
                'titulo' => 'nullable|string|max:255',
            ]);

            $imagemPath = $this->uploadArquivo($request, 'imagem', 'imagens_gt');

            $imagem = ImagensGt::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'imagem' => basename($imagemPath),
                'imagem_path' => $imagemPath,
                'titulo' => $request->titulo,
            ]);

            if ($imagem->imagem_path) {
                $imagem->imagem_path = url("storage/{$imagem->imagem_path}");
            }

            return response()->json($imagem, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar imagem'], 500);
        }
    }

    /**
     * Atualiza uma imagem existente.
     *
     * Atualiza os dados de uma imagem específica, com a opção de substituir o arquivo de imagem.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID da imagem.
     * @return JsonResponse
     *
     * @bodyParam imagem file nullable A nova imagem a ser carregada (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam titulo string nullable O título da imagem (máx. 255 caracteres). Exemplo: "Imagem Atualizada"
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "imagem": "imagem-atualizada.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/imagem-atualizada.jpg",
     *   "titulo": "Imagem Atualizada"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Imagem não encontrada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "imagem": ["O campo imagem deve ser um arquivo do tipo: jpg, jpeg, png, webp"],
     *     "titulo": ["O campo título deve ser uma string"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar imagem"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $imagem = ImagensGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'imagem' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'titulo' => 'nullable|string|max:255',
            ]);

            $dados = $request->only(['titulo']);

            if ($request->hasFile('imagem')) {
                if ($imagem->imagem_path) {
                    Storage::disk('public')->delete($imagem->imagem_path);
                }
                $imagemPath = $this->uploadArquivo($request, 'imagem', 'imagens_gt');
                $dados['imagem'] = basename($imagemPath);
                $dados['imagem_path'] = $imagemPath;
            }

            $imagem->update($dados);

            if ($imagem->imagem_path) {
                $imagem->imagem_path = url("storage/{$imagem->imagem_path}");
            }

            return response()->json($imagem, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar imagem'], 500);
        }
    }

    /**
     * Deleta uma imagem.
     *
     * Remove uma imagem específica e seu arquivo associado do armazenamento.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID da imagem.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Imagem deletada com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Imagem não encontrada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar imagem"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $imagem = ImagensGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            if ($imagem->imagem_path) {
                Storage::disk('public')->delete($imagem->imagem_path);
            }

            $imagem->delete();

            return response()->json(['message' => 'Imagem deletada com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar imagem'], 500);
        }
    }
}
