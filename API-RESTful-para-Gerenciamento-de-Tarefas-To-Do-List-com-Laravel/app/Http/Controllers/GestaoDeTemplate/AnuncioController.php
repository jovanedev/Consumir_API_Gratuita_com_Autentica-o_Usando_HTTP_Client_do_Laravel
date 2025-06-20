<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\Anuncio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

/**
 * @group Gestão de Template - Anúncios
 *
 * Endpoints para gerenciamento de anúncios associados a templates e lojas.
 */
class AnuncioController extends Controller
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

            $caminho = $pastaloja."/assets/gestaoTemplate/{$pasta}";
            $arquivoPath = $file->storeAs($caminho, $nomeUnico, 'public');

            return $arquivoPath;
        }

        return null;
    }

    /**
     * Lista todos os anúncios de um template.
     *
     * Retorna uma lista de anúncios associados ao template especificado e à loja do usuário autenticado.
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
     *       "titulo": "Anúncio Exemplo",
     *       "texto": "Descrição do anúncio",
     *       "link": "https://exemplo.com",
     *       "imagem_desktop_path": "https://storage.exemplo.com/path/to/desktop.jpg",
     *       "imagem_mobile_path": "https://storage.exemplo.com/path/to/mobile.jpg",
     *       "carregar_imagens_mobile": true
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar anúncios"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Recupera anúncios filtrados por loja_id e template_id
            $anuncios = Anuncio::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            // Transforma os caminhos das imagens em URLs completas
            $anuncios->transform(function ($anuncio) {
                $anuncio->imagem_desktop_path = url("storage/{$anuncio->imagem_desktop_path}");
                if ($anuncio->imagem_mobile_path) {
                    $anuncio->imagem_mobile_path = url("storage/{$anuncio->imagem_mobile_path}");
                }
                return $anuncio;
            });

            return response()->json($anuncios, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar anúncios'], 500);
        }
    }

    /**
     * Exibe um anúncio específico.
     *
     * Retorna os detalhes de um anúncio específico com base no ID do template e do anúncio.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do anúncio.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Anúncio Exemplo",
     *   "texto": "Descrição do anúncio",
     *   "link": "https://exemplo.com",
     *   "imagem_desktop_path": "https://storage.exemplo.com/path/to/desktop.jpg",
     *   "imagem_mobile_path": "https://storage.exemplo.com/path/to/mobile.jpg",
     *   "carregar_imagens_mobile": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Anúncio não encontrado"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Busca o anúncio por loja_id, template_id e id
            $anuncio = Anuncio::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            // Converte os caminhos das imagens em URLs completas
            $anuncio->imagem_desktop_path = url("storage/{$anuncio->imagem_desktop_path}");
            if ($anuncio->imagem_mobile_path) {
                $anuncio->imagem_mobile_path = url("storage/{$anuncio->imagem_mobile_path}");
            }

            return response()->json($anuncio, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Anúncio não encontrado'], 404);
        }
    }

    /**
     * Cria um novo anúncio.
     *
     * Cria um novo anúncio associado ao template e à loja do usuário autenticado, com upload de imagens.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam titulo string required O título do anúncio. Exemplo: "Anúncio Exemplo"
     * @bodyParam texto string required O texto descritivo do anúncio. Exemplo: "Descrição do anúncio"
     * @bodyParam link string nullable O link associado ao anúncio. Exemplo: "https://exemplo.com"
     * @bodyParam imagem_desktop file required A imagem para desktop (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam imagem_mobile file nullable A imagem para mobile (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam carregar_imagens_mobile boolean required Define se imagens mobile devem ser carregadas. Exemplo: true
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Anúncio Exemplo",
     *   "texto": "Descrição do anúncio",
     *   "link": "https://exemplo.com",
     *   "imagem_desktop_path": "https://storage.exemplo.com/path/to/desktop.jpg",
     *   "imagem_mobile_path": "https://storage.exemplo.com/path/to/mobile.jpg",
     *   "carregar_imagens_mobile": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "titulo": ["O campo título é obrigatório"],
     *     "imagem_desktop": ["O arquivo deve ser uma imagem válida"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar anúncio"
     * }
     */
    public function store(Request $request, $template_id): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Valida os campos da requisição
            $request->validate([
                'titulo' => 'required|string|max:255',
                'texto' => 'required|string|max:1000',
                'link' => 'nullable|string|max:255',
                'imagem_desktop' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
                'imagem_mobile' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'carregar_imagens_mobile' => 'required|boolean',
            ]);

            // Faz o upload das imagens
            $desktopPath = $this->uploadArquivo($request, 'imagem_desktop', 'anuncios');
            $mobilePath = $this->uploadArquivo($request, 'imagem_mobile', 'anuncios');

            // Cria o anúncio no banco de dados
            $anuncio = Anuncio::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'titulo' => $request->titulo,
                'texto' => $request->texto,
                'link' => $request->link,
                'imagem_desktop' => basename($desktopPath),
                'imagem_desktop_path' => $desktopPath,
                'imagem_mobile' => $mobilePath ? basename($mobilePath) : null,
                'imagem_mobile_path' => $mobilePath,
                'carregar_imagens_mobile' => $request->carregar_imagens_mobile,
            ]);

            // Converte os caminhos das imagens em URLs completas
            $anuncio->imagem_desktop_path = url("storage/{$anuncio->imagem_desktop_path}");
            if ($anuncio->imagem_mobile_path) {
                $anuncio->imagem_mobile_path = url("storage/{$anuncio->imagem_mobile_path}");
            }

            return response()->json($anuncio, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar anúncio'], 500);
        }
    }

    /**
     * Atualiza um anúncio existente.
     *
     * Atualiza os dados de um anúncio específico, incluindo a possibilidade de substituir imagens.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID do anúncio.
     * @return JsonResponse
     *
     * @bodyParam titulo string O título do anúncio. Exemplo: "Anúncio Atualizado"
     * @bodyParam texto string O texto descritivo do anúncio. Exemplo: "Descrição atualizada"
     * @bodyParam link string nullable O link associado ao anúncio. Exemplo: "https://exemplo.com"
     * @bodyParam imagem_desktop file A nova imagem para desktop (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam imagem_mobile file A nova imagem para mobile (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam carregar_imagens_mobile boolean Define se imagens mobile devem ser carregadas. Exemplo: true
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Anúncio Atualizado",
     *   "texto": "Descrição atualizada",
     *   "link": "https://exemplo.com",
     *   "imagem_desktop_path": "https://storage.exemplo.com/path/to/new_desktop.jpg",
     *   "imagem_mobile_path": "https://storage.exemplo.com/path/to/new_mobile.jpg",
     *   "carregar_imagens_mobile": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Anúncio não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "titulo": ["O campo título deve ser uma string"],
     *     "imagem_desktop": ["O arquivo deve ser uma imagem válida"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar anúncio"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Busca o anúncio por loja_id, template_id e id
            $anuncio = Anuncio::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            // Valida os campos da requisição
            $request->validate([
                'titulo' => 'sometimes|required|string|max:255',
                'texto' => 'sometimes|required|string|max:1000',
                'link' => 'nullable|string|max:255',
                'imagem_desktop' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'imagem_mobile' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'carregar_imagens_mobile' => 'sometimes|required|boolean',
            ]);

            // Coleta os dados enviados
            $dados = $request->only(['titulo', 'texto', 'link', 'carregar_imagens_mobile']);

            // Atualiza imagem desktop, se enviada
            if ($request->hasFile('imagem_desktop')) {
                if ($anuncio->imagem_desktop_path) {
                    Storage::disk('public')->delete($anuncio->imagem_desktop_path);
                }
                $desktopPath = $this->uploadArquivo($request, 'imagem_desktop', 'anuncios');
                $dados['imagem_desktop'] = basename($desktopPath);
                $dados['imagem_desktop_path'] = $desktopPath;
            }

            // Atualiza imagem mobile, se enviada
            if ($request->hasFile('imagem_mobile')) {
                if ($anuncio->imagem_mobile_path) {
                    Storage::disk('public')->delete($anuncio->imagem_mobile_path);
                }
                $mobilePath = $this->uploadArquivo($request, 'imagem_mobile', 'anuncios');
                $dados['imagem_mobile'] = basename($mobilePath);
                $dados['imagem_mobile_path'] = $mobilePath;
            }

            // Atualiza o anúncio no banco
            $anuncio->update($dados);

            // Converte os caminhos das imagens em URLs completas
            $anuncio->imagem_desktop_path = url("storage/{$anuncio->imagem_desktop_path}");
            if ($anuncio->imagem_mobile_path) {
                $anuncio->imagem_mobile_path = url("storage/{$anuncio->imagem_mobile_path}");
            }

            return response()->json($anuncio, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar anúncio'], 500);
        }
    }

    /**
     * Deleta um anúncio.
     *
     * Remove um anúncio específico e suas imagens associadas do armazenamento.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do anúncio.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Anúncio deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Anúncio não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar anúncio"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Busca o anúncio por loja_id, template_id e id
            $anuncio = Anuncio::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            // Deleta as imagens do disco, se existirem
            if ($anuncio->imagem_desktop_path) {
                Storage::disk('public')->delete($anuncio->imagem_desktop_path);
            }
            if ($anuncio->imagem_mobile_path) {
                Storage::disk('public')->delete($anuncio->imagem_mobile_path);
            }

            // Deleta o anúncio do banco
            $anuncio->delete();

            return response()->json(['message' => 'Anúncio deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar anúncio'], 500);
        }
    }
}
