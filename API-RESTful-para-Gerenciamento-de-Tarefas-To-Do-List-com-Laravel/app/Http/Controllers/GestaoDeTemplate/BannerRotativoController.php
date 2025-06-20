<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\BannerRotativo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

/**
 * @group Gestão de Template - Banners Rotativos
 *
 * Endpoints para gerenciamento de banners rotativos associados a templates e lojas.
 */
class BannerRotativoController extends Controller
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
     * Lista todos os banners rotativos de um template.
     *
     * Retorna uma lista de banners rotativos associados ao template especificado e à loja do usuário autenticado.
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
     *       "imagem_desktop_path": "https://storage.exemplo.com/path/to/desktop.jpg",
     *       "imagem_mobile_path": "https://storage.exemplo.com/path/to/mobile.jpg",
     *       "largura_tela": true,
     *       "efeito_movimento": false
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar banners rotativos"
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

            // Recupera banners filtrados por loja_id e template_id
            $banners = BannerRotativo::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            // Transforma os caminhos das imagens em URLs completas
            $banners->transform(function ($banner) {
                if ($banner->imagem_desktop_path) {
                    $banner->imagem_desktop_path = url("storage/{$banner->imagem_desktop_path}");
                }
                if ($banner->imagem_mobile_path) {
                    $banner->imagem_mobile_path = url("storage/{$banner->imagem_mobile_path}");
                }
                return $banner;
            });

            return response()->json($banners, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar banners rotativos'], 500);
        }
    }

    /**
     * Exibe um banner rotativo específico.
     *
     * Retorna os detalhes de um banner rotativo específico com base no ID do template e do banner.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do banner.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "imagem_desktop_path": "https://storage.exemplo.com/path/to/desktop.jpg",
     *   "imagem_mobile_path": "https://storage.exemplo.com/path/to/mobile.jpg",
     *   "largura_tela": true,
     *   "efeito_movimento": false
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Banner rotativo não encontrado"
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

            // Busca o banner por loja_id, template_id e id
            $banner = BannerRotativo::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            // Converte os caminhos das imagens em URLs completas
            if ($banner->imagem_desktop_path) {
                $banner->imagem_desktop_path = url("storage/{$banner->imagem_desktop_path}");
            }
            if ($banner->imagem_mobile_path) {
                $banner->imagem_mobile_path = url("storage/{$banner->imagem_mobile_path}");
            }

            return response()->json($banner, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Banner rotativo não encontrado'], 404);
        }
    }

    /**
     * Cria um novo banner rotativo.
     *
     * Cria um novo banner rotativo associado ao template e à loja do usuário autenticado, com upload de imagens.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam imagem_desktop file required A imagem para desktop (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam imagem_mobile file nullable A imagem para mobile (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam largura_tela boolean required Define se o banner deve ocupar a largura total da tela. Exemplo: true
     * @bodyParam efeito_movimento boolean required Define se o banner deve ter efeito de movimento. Exemplo: false
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "imagem_desktop_path": "https://storage.exemplo.com/path/to/desktop.jpg",
     *   "imagem_mobile_path": "https://storage.exemplo.com/path/to/mobile.jpg",
     *   "largura_tela": true,
     *   "efeito_movimento": false
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "imagem_desktop": ["O campo imagem_desktop é obrigatório"],
     *     "largura_tela": ["O campo largura_tela é obrigatório"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar banner rotativo"
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
                'imagem_desktop' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
                'imagem_mobile' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'largura_tela' => 'required|boolean',
                'efeito_movimento' => 'required|boolean',
            ]);

            // Faz o upload das imagens
            $desktopPath = $this->uploadArquivo($request, 'imagem_desktop', 'banner_rotativo');
            $mobilePath = $this->uploadArquivo($request, 'imagem_mobile', 'banner_rotativo');

            // Cria o banner no banco de dados
            $banner = BannerRotativo::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'imagem_desktop' => basename($desktopPath),
                'imagem_desktop_path' => $desktopPath,
                'imagem_mobile' => $mobilePath ? basename($mobilePath) : null,
                'imagem_mobile_path' => $mobilePath,
                'largura_tela' => $request->largura_tela,
                'efeito_movimento' => $request->efeito_movimento,
            ]);

            // Converte os caminhos das imagens em URLs completas
            if ($banner->imagem_desktop_path) {
                $banner->imagem_desktop_path = url("storage/{$banner->imagem_desktop_path}");
            }
            if ($banner->imagem_mobile_path) {
                $banner->imagem_mobile_path = url("storage/{$banner->imagem_mobile_path}");
            }

            return response()->json($banner, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar banner rotativo'], 500);
        }
    }

    /**
     * Atualiza um banner rotativo existente.
     *
     * Atualiza os dados de um banner rotativo específico, incluindo a possibilidade de substituir imagens.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID do banner.
     * @return JsonResponse
     *
     * @bodyParam imagem_desktop file A nova imagem para desktop (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam imagem_mobile file A nova imagem para mobile (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam largura_tela boolean Define se o banner deve ocupar a largura total da tela. Exemplo: true
     * @bodyParam efeito_movimento boolean Define se o banner deve ter efeito de movimento. Exemplo: false
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "imagem_desktop_path": "https://storage.exemplo.com/path/to/new_desktop.jpg",
     *   "imagem_mobile_path": "https://storage.exemplo.com/path/to/new_mobile.jpg",
     *   "largura_tela": true,
     *   "efeito_movimento": false
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Banner rotativo não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "imagem_desktop": ["O arquivo deve ser uma imagem válida"],
     *     "largura_tela": ["O campo largura_tela é obrigatório"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar banner rotativo"
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

            // Busca o banner por loja_id, template_id e id
            $banner = BannerRotativo::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            // Valida os campos da requisição
            $request->validate([
                'imagem_desktop' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'imagem_mobile' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'largura_tela' => 'sometimes|required|boolean',
                'efeito_movimento' => 'sometimes|required|boolean',
            ]);

            // Coleta os dados enviados
            $dados = $request->only(['largura_tela', 'efeito_movimento']);

            // Atualiza imagem desktop, se enviada
            if ($request->hasFile('imagem_desktop')) {
                if ($banner->imagem_desktop_path) {
                    Storage::disk('public')->delete($banner->imagem_desktop_path);
                }
                $desktopPath = $this->uploadArquivo($request, 'imagem_desktop', 'banner_rotativo');
                $dados['imagem_desktop'] = basename($desktopPath);
                $dados['imagem_desktop_path'] = $desktopPath;
            }

            // Atualiza imagem mobile, se enviada
            if ($request->hasFile('imagem_mobile')) {
                if ($banner->imagem_mobile_path) {
                    Storage::disk('public')->delete($banner->imagem_mobile_path);
                }
                $mobilePath = $this->uploadArquivo($request, 'imagem_mobile', 'banner_rotativo');
                $dados['imagem_mobile'] = basename($mobilePath);
                $dados['imagem_mobile_path'] = $mobilePath;
            }

            // Atualiza o banner no banco
            $banner->update($dados);

            // Converte os caminhos das imagens em URLs completas
            if ($banner->imagem_desktop_path) {
                $banner->imagem_desktop_path = url("storage/{$banner->imagem_desktop_path}");
            }
            if ($banner->imagem_mobile_path) {
                $banner->imagem_mobile_path = url("storage/{$banner->imagem_mobile_path}");
            }

            return response()->json($banner, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar banner rotativo'], 500);
        }
    }

    /**
     * Deleta um banner rotativo.
     *
     * Remove um banner rotativo específico e suas imagens associadas do armazenamento.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do banner.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Banner rotativo deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Banner rotativo não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar banner rotativo"
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

            // Busca o banner por loja_id, template_id e id
            $banner = BannerRotativo::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            // Deleta as imagens do disco, se existirem
            if ($banner->imagem_desktop_path) {
                Storage::disk('public')->delete($banner->imagem_desktop_path);
            }
            if ($banner->imagem_mobile_path) {
                Storage::disk('public')->delete($banner->imagem_mobile_path);
            }

            // Deleta o banner do banco
            $banner->delete();

            return response()->json(['message' => 'Banner rotativo deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar banner rotativo'], 500);
        }
    }
}
