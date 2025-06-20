<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

/**
 * @group Gestão de Template - Vídeos
 *
 * Endpoints para gerenciamento de vídeos associados a templates e lojas.
 */
class VideoController extends Controller
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
     * Recupera o nome da pasta associada à loja.
     *
     * @return string|null O nome da pasta da loja ou null se não encontrado.
     */
    private function dadosLojaPasta(): ?string
    {
        $loja_id = $this->getLojaId();
        $dados_loja = DB::table('lojas')->where('id', $loja_id)->first();
        return $dados_loja->pasta ?? null;
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
            if (!$pastaloja) {
                return null;
            }

            $caminho = $pastaloja . "/assets/gestaoTemplate/{$pasta}";
            $arquivoPath = $file->storeAs($caminho, $nomeUnico, 'public');

            return $arquivoPath;
        }

        return null;
    }

    /**
     * Lista todos os vídeos de um template.
     *
     * Retorna uma lista de vídeos associados ao template e à loja do usuário autenticado.
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
     *       "aumentar_largura_tela": true,
     *       "tipo_reproducao": "automatico_sem_som",
     *       "link_youtube": "https://www.youtube.com/watch?v=example",
     *       "imagem": "thumbnail.jpg",
     *       "imagem_path": "https://storage.exemplo.com/path/to/thumbnail.jpg",
     *       "titulo": "Vídeo Promocional",
     *       "descricao": "Assista ao nosso novo vídeo promocional!",
     *       "texto_botao": "Saiba Mais",
     *       "link_botao": "https://exemplo.com/promo"
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar vídeos"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $videos = Video::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            $videos->transform(function ($video) {
                if ($video->imagem_path) {
                    $video->imagem_path = url("storage/{$video->imagem_path}");
                }
                return $video;
            });

            return response()->json(['data' => $videos], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar vídeos'], 500);
        }
    }

    /**
     * Exibe um vídeo específico.
     *
     * Retorna os detalhes de um vídeo específico com base no ID do template e do vídeo.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do vídeo.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "aumentar_largura_tela": true,
     *   "tipo_reproducao": "automatico_sem_som",
     *   "link_youtube": "https://www.youtube.com/watch?v=example",
     *   "imagem": "thumbnail.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/thumbnail.jpg",
     *   "titulo": "Vídeo Promocional",
     *   "descricao": "Assista ao nosso novo vídeo promocional!",
     *   "texto_botao": "Saiba Mais",
     *   "link_botao": "https://exemplo.com/promo"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Vídeo não encontrado"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $video = Video::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            if ($video->imagem_path) {
                $video->imagem_path = url("storage/{$video->imagem_path}");
            }

            return response()->json($video, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Vídeo não encontrado'], 404);
        }
    }

    /**
     * Cria um novo vídeo.
     *
     * Cria um novo vídeo associado ao template e à loja do usuário autenticado, com upload de imagem.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam aumentar_largura_tela boolean required Define se o vídeo deve ocupar a largura total da tela. Exemplo: true
     * @bodyParam tipo_reproducao string required Tipo de reprodução do vídeo (automatico_sem_som ou manual_com_som). Exemplo: automatico_sem_som
     * @bodyParam link_youtube string required URL do vídeo no YouTube (máx. 255 caracteres). Exemplo: https://www.youtube.com/watch?v=example
     * @bodyParam imagem file required Imagem de miniatura do vídeo (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam titulo string required Título do vídeo (máx. 255 caracteres). Exemplo: Vídeo Promocional
     * @bodyParam descricao string required Descrição do vídeo (máx. 1000 caracteres). Exemplo: Assista ao nosso novo vídeo promocional!
     * @bodyParam texto_botao string nullable Texto do botão (máx. 255 caracteres). Exemplo: Saiba Mais
     * @bodyParam link_botao string nullable URL do botão (máx. 255 caracteres). Exemplo: https://exemplo.com/promo
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "aumentar_largura_tela": true,
     *   "tipo_reproducao": "automatico_sem_som",
     *   "link_youtube": "https://www.youtube.com/watch?v=example",
     *   "imagem": "thumbnail.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/thumbnail.jpg",
     *   "titulo": "Vídeo Promocional",
     *   "descricao": "Assista ao nosso novo vídeo promocional!",
     *   "texto_botao": "Saiba Mais",
     *   "link_botao": "https://exemplo.com/promo"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "aumentar_largura_tela": ["O campo aumentar_largura_tela é obrigatório"],
     *     "tipo_reproducao": ["O campo tipo_reproducao deve ser automatico_sem_som ou manual_com_som"],
     *     "link_youtube": ["O campo link_youtube deve ser uma URL válida"],
     *     "imagem": ["O campo imagem deve ser um arquivo do tipo: jpg, jpeg, png, webp"],
     *     "titulo": ["O campo título é obrigatório"],
     *     "descricao": ["O campo descrição é obrigatório"],
     *     "texto_botao": ["O campo texto_botao deve ser uma string"],
     *     "link_botao": ["O campo link_botao deve ser uma URL válida"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar vídeo"
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
                'aumentar_largura_tela' => 'required|boolean',
                'tipo_reproducao' => 'required|in:automatico_sem_som,manual_com_som',
                'link_youtube' => 'required|string|max:255|url',
                'imagem' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
                'titulo' => 'required|string|max:255',
                'descricao' => 'required|string|max:1000',
                'texto_botao' => 'nullable|string|max:255',
                'link_botao' => 'nullable|string|max:255|url',
            ]);

            $imagemPath = $this->uploadArquivo($request, 'imagem', 'videos');

            $video = Video::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'aumentar_largura_tela' => $request->aumentar_largura_tela,
                'tipo_reproducao' => $request->tipo_reproducao,
                'link_youtube' => $request->link_youtube,
                'imagem' => basename($imagemPath),
                'imagem_path' => $imagemPath,
                'titulo' => $request->titulo,
                'descricao' => $request->descricao,
                'texto_botao' => $request->texto_botao,
                'link_botao' => $request->link_botao,
            ]);

            if ($video->imagem_path) {
                $video->imagem_path = url("storage/{$video->imagem_path}");
            }

            return response()->json($video, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar vídeo'], 500);
        }
    }

    /**
     * Atualiza um vídeo existente.
     *
     * Atualiza os dados de um vídeo específico, com a opção de substituir a imagem.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID do vídeo.
     * @return JsonResponse
     *
     * @bodyParam aumentar_largura_tela boolean Define se o vídeo deve ocupar a largura total da tela. Exemplo: false
     * @bodyParam tipo_reproducao string Tipo de reprodução do vídeo (automatico_sem_som ou manual_com_som). Exemplo: manual_com_som
     * @bodyParam link_youtube string URL do vídeo no YouTube (máx. 255 caracteres). Exemplo: https://www.youtube.com/watch?v=newexample
     * @bodyParam imagem file nullable Nova imagem de miniatura do vídeo (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam titulo string Título do vídeo (máx. 255 caracteres). Exemplo: Vídeo Promocional Atualizado
     * @bodyParam descricao string Descrição do vídeo (máx. 1000 caracteres). Exemplo: Novo vídeo promocional atualizado!
     * @bodyParam texto_botao string nullable Texto do botão (máx. 255 caracteres). Exemplo: Veja Agora
     * @bodyParam link_botao string nullable URL do botão (máx. 255 caracteres). Exemplo: https://exemplo.com/nova-promo
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "aumentar_largura_tela": false,
     *   "tipo_reproducao": "manual_com_som",
     *   "link_youtube": "https://www.youtube.com/watch?v=newexample",
     *   "imagem": "new-thumbnail.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/new-thumbnail.jpg",
     *   "titulo": "Vídeo Promocional Atualizado",
     *   "descricao": "Novo vídeo promocional atualizado!",
     *   "texto_botao": "Veja Agora",
     *   "link_botao": "https://exemplo.com/nova-promo"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Vídeo não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "aumentar_largura_tela": ["O campo aumentar_largura_tela deve ser um booleano"],
     *     "tipo_reproducao": ["O campo tipo_reproducao deve ser automatico_sem_som ou manual_com_som"],
     *     "link_youtube": ["O campo link_youtube deve ser uma URL válida"],
     *     "imagem": ["O campo imagem deve ser um arquivo do tipo: jpg, jpeg, png, webp"],
     *     "titulo": ["O campo título deve ser uma string"],
     *     "descricao": ["O campo descrição deve ser uma string"],
     *     "texto_botao": ["O campo texto_botao deve ser uma string"],
     *     "link_botao": ["O campo link_botao deve ser uma URL válida"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar vídeo"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $video = Video::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'aumentar_largura_tela' => 'sometimes|required|boolean',
                'tipo_reproducao' => 'sometimes|required|in:automatico_sem_som,manual_com_som',
                'link_youtube' => 'sometimes|required|string|max:255|url',
                'imagem' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'titulo' => 'sometimes|required|string|max:255',
                'descricao' => 'sometimes|required|string|max:1000',
                'texto_botao' => 'nullable|string|max:255',
                'link_botao' => 'nullable|string|max:255|url',
            ]);

            $dados = $request->only([
                'aumentar_largura_tela',
                'tipo_reproducao',
                'link_youtube',
                'titulo',
                'descricao',
                'texto_botao',
                'link_botao',
            ]);

            if ($request->hasFile('imagem')) {
                if ($video->imagem_path) {
                    Storage::disk('public')->delete($video->imagem_path);
                }
                $imagemPath = $this->uploadArquivo($request, 'imagem', 'videos');
                $dados['imagem'] = basename($imagemPath);
                $dados['imagem_path'] = $imagemPath;
            }

            $video->update($dados);

            if ($video->imagem_path) {
                $video->imagem_path = url("storage/{$video->imagem_path}");
            }

            return response()->json($video, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar vídeo'], 500);
        }
    }

    /**
     * Deleta um vídeo.
     *
     * Remove um vídeo específico e sua imagem associada do armazenamento.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do vídeo.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Vídeo deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Vídeo não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar vídeo"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $video = Video::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            if ($video->imagem_path) {
                Storage::disk('public')->delete($video->imagem_path);
            }

            $video->delete();

            return response()->json(['message' => 'Vídeo deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar vídeo'], 500);
        }
    }
}
