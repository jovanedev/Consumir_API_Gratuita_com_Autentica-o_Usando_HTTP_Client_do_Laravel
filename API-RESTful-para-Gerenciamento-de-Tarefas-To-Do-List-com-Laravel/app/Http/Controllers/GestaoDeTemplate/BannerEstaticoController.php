<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\BannerEstatico;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

/**
 * @group Gestão de  Template - Banners Estáticos
 *
 * Endpoints para gerenciamento de banners estáticos associados a templates e lojas.
 */
class BannerEstaticoController extends Controller
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
     * Lista todos os banners estáticos de um template.
     *
     * Retorna uma lista de banners estáticos associados ao template especificado e à loja do usuário autenticado.
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
     *       "titulo": "Banner Exemplo",
     *       "link": "https://exemplo.com",
     *       "imagem_path": "https://storage.exemplo.com/path/to/banner.jpg",
     *       "exibir": true
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar banners estáticos"
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
            $banners = BannerEstatico::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            // Transforma os caminhos das imagens em URLs completas
            $banners->transform(function ($banner) {
                if ($banner->imagem_path) {
                    $banner->imagem_path = url("storage/{$banner->imagem_path}");
                }
                return $banner;
            });

            return response()->json($banners, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar banners estáticos'], 500);
        }
    }

    /**
     * Exibe um banner estático específico.
     *
     * Retorna os detalhes de um banner estático específico com base no ID do template e do banner.
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
     *   "titulo": "Banner Exemplo",
     *   "link": "https://exemplo.com",
     *   "imagem_path": "https://storage.exemplo.com/path/to/banner.jpg",
     *   "exibir": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Banner estático não encontrado"
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
            $banner = BannerEstatico::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            // Converte o caminho da imagem em URL completa
            if ($banner->imagem_path) {
                $banner->imagem_path = url("storage/{$banner->imagem_path}");
            }

            return response()->json($banner, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Banner estático não encontrado'], 404);
        }
    }

    /**
     * Cria um novo banner estático.
     *
     * Cria um novo banner estático associado ao template e à loja do usuário autenticado, com upload de imagem.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam imagem file required A imagem do banner (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam titulo string nullable O título do banner. Exemplo: "Banner Exemplo"
     * @bodyParam link string nullable O link associado ao banner (deve ser uma URL válida). Exemplo: "https://exemplo.com"
     * @bodyParam exibir boolean required Define se o banner deve ser exibido. Exemplo: true
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Banner Exemplo",
     *   "link": "https://exemplo.com",
     *   "imagem_path": "https://storage.exemplo.com/path/to/banner.jpg",
     *   "exibir": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "imagem": ["O campo imagem é obrigatório"],
     *     "link": ["O link deve ser uma URL válida"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar banner estático"
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
                'imagem' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
                'titulo' => 'nullable|string|max:255',
                'link' => 'nullable|string|max:255|url',
                'exibir' => 'required|boolean',
            ]);

            // Faz o upload da imagem
            $imagemPath = $this->uploadArquivo($request, 'imagem', 'banner_estatico');

            // Cria o banner no banco de dados
            $banner = BannerEstatico::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'imagem' => basename($imagemPath),
                'imagem_path' => $imagemPath,
                'titulo' => $request->titulo,
                'link' => $request->link,
                'exibir' => $request->exibir,
            ]);

            // Converte o caminho da imagem em URL completa
            if ($banner->imagem_path) {
                $banner->imagem_path = url("storage/{$banner->imagem_path}");
            }

            return response()->json($banner, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar banner estático'], 500);
        }
    }

    /**
     * Atualiza um banner estático existente.
     *
     * Atualiza os dados de um banner estático específico, incluindo a possibilidade de substituir a imagem.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID do banner.
     * @return JsonResponse
     *
     * @bodyParam imagem file A nova imagem do banner (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam titulo string nullable O título do banner. Exemplo: "Banner Atualizado"
     * @bodyParam link string nullable O link associado ao banner (deve ser uma URL válida). Exemplo: "https://exemplo.com"
     * @bodyParam exibir boolean Define se o banner deve ser exibido. Exemplo: true
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Banner Atualizado",
     *   "link": "https://exemplo.com",
     *   "imagem_path": "https://storage.exemplo.com/path/to/new_banner.jpg",
     *   "exibir": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Banner estático não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "imagem": ["O arquivo deve ser uma imagem válida"],
     *     "link": ["O link deve ser uma URL válida"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar banner estático"
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
            $banner = BannerEstatico::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            // Valida os campos da requisição
            $request->validate([
                'imagem' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'titulo' => 'nullable|string|max:255',
                'link' => 'nullable|string|max:255|url',
                'exibir' => 'sometimes|required|boolean',
            ]);

            // Coleta os dados enviados
            $dados = $request->only(['titulo', 'link', 'exibir']);

            // Atualiza imagem, se enviada
            if ($request->hasFile('imagem')) {
                if ($banner->imagem_path) {
                    Storage::disk('public')->delete($banner->imagem_path);
                }
                $imagemPath = $this->uploadArquivo($request, 'imagem', 'banner_estatico');
                $dados['imagem'] = basename($imagemPath);
                $dados['imagem_path'] = $imagemPath;
            }

            // Atualiza o banner no banco
            $banner->update($dados);

            // Converte o caminho da imagem em URL completa
            if ($banner->imagem_path) {
                $banner->imagem_path = url("storage/{$banner->imagem_path}");
            }

            return response()->json($banner, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar banner estático'], 500);
        }
    }

    /**
     * Deleta um banner estático.
     *
     * Remove um banner estático específico e sua imagem associada do armazenamento.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do banner.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Banner estático deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Banner estático não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar banner estático"
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
            $banner = BannerEstatico::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            // Deleta a imagem do disco, se existir
            if ($banner->imagem_path) {
                Storage::disk('public')->delete($banner->imagem_path);
            }

            // Deleta o banner do banco
            $banner->delete();

            return response()->json(['message' => 'Banner estático deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar banner estático'], 500);
        }
    }
}
