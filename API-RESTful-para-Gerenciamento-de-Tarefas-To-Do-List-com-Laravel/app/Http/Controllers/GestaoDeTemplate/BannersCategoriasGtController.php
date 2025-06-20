<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\BannersCategoriasGt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

/**
 * @group Gestão de Template - Banners de Categorias
 *
 * Endpoints para gerenciamento de banners de categorias associados a templates e lojas.
 */
class BannersCategoriasGtController extends Controller
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
     * Lista todos os banners de categorias de um template.
     *
     * Retorna uma lista de banners de categorias associados ao template especificado e à loja do usuário autenticado.
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
     *       "categoria_id": 1,
     *       "titulo": "Banner Categoria Exemplo",
     *       "mostrar_texto_fora_imagem": true,
     *       "mostrar_banners_carrossel": false,
     *       "mesma_altura_banners": true,
     *       "remover_espacos_banners": false,
     *       "banners_por_linha": 3,
     *       "imagem_desktop_path": "https://storage.exemplo.com/path/to/desktop.jpg",
     *       "imagem_mobile_path": "https://storage.exemplo.com/path/to/mobile.jpg",
     *       "carregar_imagens_celular": true
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar banners de categorias"
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
            $banners = BannersCategoriasGt::where('loja_id', $loja_id)
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
            return response()->json(['error' => 'Erro ao listar banners de categorias'], 500);
        }
    }

    /**
     * Exibe um banner de categoria específico.
     *
     * Retorna os detalhes de um banner de categoria específico com base no ID do template e do banner.
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
     *   "categoria_id": 1,
     *   "titulo": "Banner Categoria Exemplo",
     *   "mostrar_texto_fora_imagem": true,
     *   "mostrar_banners_carrossel": false,
     *   "mesma_altura_banners": true,
     *   "remover_espacos_banners": false,
     *   "banners_por_linha": 3,
     *   "imagem_desktop_path": "https://storage.exemplo.com/path/to/desktop.jpg",
     *   "imagem_mobile_path": "https://storage.exemplo.com/path/to/mobile.jpg",
     *   "carregar_imagens_celular": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Banner de categoria não encontrado"
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
            $banner = BannersCategoriasGt::where('loja_id', $loja_id)
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
            return response()->json(['error' => 'Banner de categoria não encontrado'], 404);
        }
    }

    /**
     * Cria um novo banner de categoria.
     *
     * Cria um novo banner de categoria associado ao template e à loja do usuário autenticado, com upload de imagens.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam categoria_id integer required O ID da categoria associada (deve existir na tabela categorias). Exemplo: 1
     * @bodyParam titulo string nullable O título do banner. Exemplo: "Banner Categoria Exemplo"
     * @bodyParam mostrar_texto_fora_imagem boolean required Define se o texto deve ser exibido fora da imagem. Exemplo: true
     * @bodyParam mostrar_banners_carrossel boolean required Define se os banners serão exibidos em carrossel. Exemplo: false
     * @bodyParam mesma_altura_banners boolean required Define se os banners devem ter a mesma altura. Exemplo: true
     * @bodyParam remover_espacos_banners boolean required Define se os espaços entre banners devem ser removidos. Exemplo: false
     * @bodyParam banners_por_linha integer required Número de banners por linha (1 a 10). Exemplo: 3
     * @bodyParam imagem_desktop file required A imagem para desktop (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam imagem_mobile file nullable A imagem para mobile (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam carregar_imagens_celular boolean required Define se imagens para celular devem ser carregadas. Exemplo: true
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "categoria_id": 1,
     *   "titulo": "Banner Categoria Exemplo",
     *   "mostrar_texto_fora_imagem": true,
     *   "mostrar_banners_carrossel": false,
     *   "mesma_altura_banners": true,
     *   "remover_espacos_banners": false,
     *   "banners_por_linha": 3,
     *   "imagem_desktop_path": "https://storage.exemplo.com/path/to/desktop.jpg",
     *   "imagem_mobile_path": "https://storage.exemplo.com/path/to/mobile.jpg",
     *   "carregar_imagens_celular": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "categoria_id": ["O campo categoria_id é obrigatório"],
     *     "banners_por_linha": ["O campo deve ser um inteiro entre 1 e 10"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar banner de categoria"
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
                'categoria_id' => 'required|exists:categorias,id',
                'titulo' => 'nullable|string|max:255',
                'mostrar_texto_fora_imagem' => 'required|boolean',
                'mostrar_banners_carrossel' => 'required|boolean',
                'mesma_altura_banners' => 'required|boolean',
                'remover_espacos_banners' => 'required|boolean',
                'banners_por_linha' => 'required|integer|min:1|max:10',
                'imagem_desktop' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
                'imagem_mobile' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'carregar_imagens_celular' => 'required|boolean',
            ]);

            // Faz o upload das imagens
            $desktopPath = $this->uploadArquivo($request, 'imagem_desktop', 'banners_categorias');
            $mobilePath = $this->uploadArquivo($request, 'imagem_mobile', 'banners_categorias');

            // Cria o banner no banco de dados
            $banner = BannersCategoriasGt::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'categoria_id' => $request->categoria_id,
                'titulo' => $request->titulo,
                'mostrar_texto_fora_imagem' => $request->mostrar_texto_fora_imagem,
                'mostrar_banners_carrossel' => $request->mostrar_banners_carrossel,
                'mesma_altura_banners' => $request->mesma_altura_banners,
                'remover_espacos_banners' => $request->remover_espacos_banners,
                'banners_por_linha' => $request->banners_por_linha,
                'imagem_desktop' => basename($desktopPath),
                'imagem_desktop_path' => $desktopPath,
                'imagem_mobile' => $mobilePath ? basename($mobilePath) : null,
                'imagem_mobile_path' => $mobilePath,
                'carregar_imagens_celular' => $request->carregar_imagens_celular,
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
            return response()->json(['error' => 'Erro ao criar banner de categoria'], 500);
        }
    }

    /**
     * Atualiza um banner de categoria existente.
     *
     * Atualiza os dados de um banner de categoria específico, incluindo a possibilidade de substituir imagens.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID do banner.
     * @return JsonResponse
     *
     * @bodyParam categoria_id integer O ID da categoria associada (deve existir na tabela categorias). Exemplo: 1
     * @bodyParam titulo string nullable O título do banner. Exemplo: "Banner Categoria Atualizado"
     * @bodyParam mostrar_texto_fora_imagem boolean Define se o texto deve ser exibido fora da imagem. Exemplo: true
     * @bodyParam mostrar_banners_carrossel boolean Define se os banners serão exibidos em carrossel. Exemplo: false
     * @bodyParam mesma_altura_banners boolean Define se os banners devem ter a mesma altura. Exemplo: true
     * @bodyParam remover_espacos_banners boolean Define se os espaços entre banners devem ser removidos. Exemplo: false
     * @bodyParam banners_por_linha integer Número de banners por linha (1 a 10). Exemplo: 3
     * @bodyParam imagem_desktop file A nova imagem para desktop (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam imagem_mobile file A nova imagem para mobile (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam carregar_imagens_celular boolean Define se imagens para celular devem ser carregadas. Exemplo: true
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "categoria_id": 1,
     *   "titulo": "Banner Categoria Atualizado",
     *   "mostrar_texto_fora_imagem": true,
     *   "mostrar_banners_carrossel": false,
     *   "mesma_altura_banners": true,
     *   "remover_espacos_banners": false,
     *   "banners_por_linha": 3,
     *   "imagem_desktop_path": "https://storage.exemplo.com/path/to/new_desktop.jpg",
     *   "imagem_mobile_path": "https://storage.exemplo.com/path/to/new_mobile.jpg",
     *   "carregar_imagens_celular": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Banner de categoria não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "categoria_id": ["O campo categoria_id deve existir"],
     *     "banners_por_linha": ["O campo deve ser um inteiro entre 1 e 10"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar banner de categoria"
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
            $banner = BannersCategoriasGt::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            // Valida os campos da requisição
            $request->validate([
                'categoria_id' => 'sometimes|required|exists:categorias,id',
                'titulo' => 'nullable|string|max:255',
                'mostrar_texto_fora_imagem' => 'sometimes|required|boolean',
                'mostrar_banners_carrossel' => 'sometimes|required|boolean',
                'mesma_altura_banners' => 'sometimes|required|boolean',
                'remover_espacos_banners' => 'sometimes|required|boolean',
                'banners_por_linha' => 'sometimes|required|integer|min:1|max:10',
                'imagem_desktop' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'imagem_mobile' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'carregar_imagens_celular' => 'sometimes|required|boolean',
            ]);

            // Coleta os dados enviados
            $dados = $request->only([
                'categoria_id',
                'titulo',
                'mostrar_texto_fora_imagem',
                'mostrar_banners_carrossel',
                'mesma_altura_banners',
                'remover_espacos_banners',
                'banners_por_linha',
                'carregar_imagens_celular',
            ]);

            // Atualiza imagem desktop, se enviada
            if ($request->hasFile('imagem_desktop')) {
                if ($banner->imagem_desktop_path) {
                    Storage::disk('public')->delete($banner->imagem_desktop_path);
                }
                $desktopPath = $this->uploadArquivo($request, 'imagem_desktop', 'banners_categorias');
                $dados['imagem_desktop'] = basename($desktopPath);
                $dados['imagem_desktop_path'] = $desktopPath;
            }

            // Atualiza imagem mobile, se enviada
            if ($request->hasFile('imagem_mobile')) {
                if ($banner->imagem_mobile_path) {
                    Storage::disk('public')->delete($banner->imagem_mobile_path);
                }
                $mobilePath = $this->uploadArquivo($request, 'imagem_mobile', 'banners_categorias');
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
            return response()->json(['error' => 'Erro ao atualizar banner de categoria'], 500);
        }
    }

    /**
     * Deleta um banner de categoria.
     *
     * Remove um banner de categoria específico e suas imagens associadas do armazenamento.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do banner.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Banner de categoria deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Banner de categoria não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar banner de categoria"
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
            $banner = BannersCategoriasGt::where('loja_id', $loja_id)
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

            return response()->json(['message' => 'Banner de categoria deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar banner de categoria'], 500);
        }
    }
}
