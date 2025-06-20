<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\InfoFretePagamento;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

/**
 * @group Gestão de Template - Informações de Frete e Pagamento
 *
 * Endpoints para gerenciamento de informações de frete e pagamento associadas a templates e lojas.
 */
class InfoFretePagamentoController extends Controller
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
     * Lista todas as informações de frete e pagamento de um template.
     *
     * Retorna uma lista de informações de frete e pagamento associadas ao template especificado e à loja do usuário autenticado.
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
     *       "usar_cores_secao": true,
     *       "cor_fundo": "#FFFFFF",
     *       "cor_texto": "#000000",
     *       "mostrar_banners_home": true,
     *       "imagem": "frete-imagem.jpg",
     *       "imagem_path": "https://storage.exemplo.com/path/to/frete-imagem.jpg",
     *       "icone": "frete-icone.png",
     *       "icone_path": "https://storage.exemplo.com/path/to/frete-icone.png",
     *       "titulo": "Frete Grátis",
     *       "descricao": "Frete grátis para compras acima de R$100",
     *       "link": "https://exemplo.com/frete"
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar informações de frete e pagamento"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $infoFretePagamentos = InfoFretePagamento::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            $infoFretePagamentos->transform(function ($info) {
                if ($info->imagem_path) {
                    $info->imagem_path = url("storage/{$info->imagem_path}");
                }
                if ($info->icone_path) {
                    $info->icone_path = url("storage/{$info->icone_path}");
                }
                return $info;
            });

            return response()->json($infoFretePagamentos, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar informações de frete e pagamento'], 500);
        }
    }

    /**
     * Exibe uma informação de frete e pagamento específica.
     *
     * Retorna os detalhes de uma informação de frete e pagamento específica com base no ID do template e da informação.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID da informação de frete e pagamento.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "usar_cores_secao": true,
     *   "cor_fundo": "#FFFFFF",
     *   "cor_texto": "#000000",
     *   "mostrar_banners_home": true,
     *   "imagem": "frete-imagem.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/frete-imagem.jpg",
     *   "icone": "frete-icone.png",
     *   "icone_path": "https://storage.exemplo.com/path/to/frete-icone.png",
     *   "titulo": "Frete Grátis",
     *   "descricao": "Frete grátis para compras acima de R$100",
     *   "link": "https://exemplo.com/frete"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Informação de frete e pagamento não encontrada"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $infoFretePagamento = InfoFretePagamento::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            if ($infoFretePagamento->imagem_path) {
                $infoFretePagamento->imagem_path = url("storage/{$infoFretePagamento->imagem_path}");
            }
            if ($infoFretePagamento->icone_path) {
                $infoFretePagamento->icone_path = url("storage/{$infoFretePagamento->icone_path}");
            }

            return response()->json($infoFretePagamento, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Informação de frete e pagamento não encontrada'], 404);
        }
    }

    /**
     * Cria uma nova informação de frete e pagamento.
     *
     * Cria uma nova informação de frete e pagamento associada ao template e à loja do usuário autenticado, com upload de imagem e ícone.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam usar_cores_secao boolean required Define se as cores da seção devem ser usadas. Exemplo: true
     * @bodyParam cor_fundo string nullable Cor de fundo em formato hexadecimal (ex.: #FFFFFF). Exemplo: #FFFFFF
     * @bodyParam cor_texto string nullable Cor do texto em formato hexadecimal (ex.: #000000). Exemplo: #000000
     * @bodyParam mostrar_banners_home boolean required Define se os banners devem ser exibidos na página inicial. Exemplo: true
     * @bodyParam imagem file required Imagem associada (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam icone file required Ícone associado (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam titulo string required Título da informação (máx. 255 caracteres). Exemplo: "Frete Grátis"
     * @bodyParam descricao string required Descrição da informação (máx. 1000 caracteres). Exemplo: "Frete grátis para compras acima de R$100"
     * @bodyParam link string required URL associada à informação (máx. 255 caracteres). Exemplo: "https://exemplo.com/frete"
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "usar_cores_secao": true,
     *   "cor_fundo": "#FFFFFF",
     *   "cor_texto": "#000000",
     *   "mostrar_banners_home": true,
     *   "imagem": "frete-imagem.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/frete-imagem.jpg",
     *   "icone": "frete-icone.png",
     *   "icone_path": "https://storage.exemplo.com/path/to/frete-icone.png",
     *   "titulo": "Frete Grátis",
     *   "descricao": "Frete grátis para compras acima de R$100",
     *   "link": "https://exemplo.com/frete"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "usar_cores_secao": ["O campo usar_cores_secao é obrigatório"],
     *     "cor_fundo": ["O campo cor_fundo deve ser um código hexadecimal válido"],
     *     "imagem": ["O campo imagem deve ser um arquivo do tipo: jpg, jpeg, png, webp"],
     *     "titulo": ["O campo título é obrigatório"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar informação de frete e pagamento"
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
                'usar_cores_secao' => 'required|boolean',
                'cor_fundo' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'cor_texto' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'mostrar_banners_home' => 'required|boolean',
                'imagem' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
                'icone' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
                'titulo' => 'required|string|max:255',
                'descricao' => 'required|string|max:1000',
                'link' => 'required|string|max:255|url',
            ]);

            $imagemPath = $this->uploadArquivo($request, 'imagem', 'info_frete_pagamento');
            $iconePath = $this->uploadArquivo($request, 'icone', 'info_frete_pagamento');

            $infoFretePagamento = InfoFretePagamento::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'usar_cores_secao' => $request->usar_cores_secao,
                'cor_fundo' => $request->cor_fundo,
                'cor_texto' => $request->cor_texto,
                'mostrar_banners_home' => $request->mostrar_banners_home,
                'imagem' => basename($imagemPath),
                'imagem_path' => $imagemPath,
                'icone' => basename($iconePath),
                'icone_path' => $iconePath,
                'titulo' => $request->titulo,
                'descricao' => $request->descricao,
                'link' => $request->link,
            ]);

            if ($infoFretePagamento->imagem_path) {
                $infoFretePagamento->imagem_path = url("storage/{$infoFretePagamento->imagem_path}");
            }
            if ($infoFretePagamento->icone_path) {
                $infoFretePagamento->icone_path = url("storage/{$infoFretePagamento->icone_path}");
            }

            return response()->json($infoFretePagamento, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar informação de frete e pagamento'], 500);
        }
    }

    /**
     * Atualiza uma informação de frete e pagamento existente.
     *
     * Atualiza os dados de uma informação de frete e pagamento específica, com a opção de substituir imagem e ícone.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID da informação de frete e pagamento.
     * @return JsonResponse
     *
     * @bodyParam usar_cores_secao boolean Define se as cores da seção devem ser usadas. Exemplo: true
     * @bodyParam cor_fundo string nullable Cor de fundo em formato hexadecimal (ex.: #FFFFFF). Exemplo: #FFFFFF
     * @bodyParam cor_texto string nullable Cor do texto em formato hexadecimal (ex.: #000000). Exemplo: #000000
     * @bodyParam mostrar_banners_home boolean Define se os banners devem ser exibidos na página inicial. Exemplo: true
     * @bodyParam imagem file nullable Nova imagem associada (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam icone file nullable Novo ícone associado (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam titulo string Título da informação (máx. 255 caracteres). Exemplo: "Frete Grátis"
     * @bodyParam descricao string Descrição da informação (máx. 1000 caracteres). Exemplo: "Frete grátis para compras acima de R$100"
     * @bodyParam link string URL associada à informação (máx. 255 caracteres). Exemplo: "https://exemplo.com/frete"
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "usar_cores_secao": true,
     *   "cor_fundo": "#FFFFFF",
     *   "cor_texto": "#000000",
     *   "mostrar_banners_home": true,
     *   "imagem": "frete-imagem-atualizada.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/frete-imagem-atualizada.jpg",
     *   "icone": "frete-icone-atualizado.png",
     *   "icone_path": "https://storage.exemplo.com/path/to/frete-icone-atualizado.png",
     *   "titulo": "Frete Grátis",
     *   "descricao": "Frete grátis para compras acima de R$100",
     *   "link": "https://exemplo.com/frete"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Informação de frete e pagamento não encontrada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "usar_cores_secao": ["O campo usar_cores_secao deve ser um booleano"],
     *     "cor_fundo": ["O campo cor_fundo deve ser um código hexadecimal válido"],
     *     "imagem": ["O campo imagem deve ser um arquivo do tipo: jpg, jpeg, png, webp"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar informação de frete e pagamento"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $infoFretePagamento = InfoFretePagamento::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'usar_cores_secao' => 'sometimes|required|boolean',
                'cor_fundo' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'cor_texto' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'mostrar_banners_home' => 'sometimes|required|boolean',
                'imagem' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'icone' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'titulo' => 'sometimes|required|string|max:255',
                'descricao' => 'sometimes|required|string|max:1000',
                'link' => 'sometimes|required|string|max:255|url',
            ]);

            $dados = $request->only([
                'usar_cores_secao',
                'cor_fundo',
                'cor_texto',
                'mostrar_banners_home',
                'titulo',
                'descricao',
                'link',
            ]);

            if ($request->hasFile('imagem')) {
                if ($infoFretePagamento->imagem_path) {
                    Storage::disk('public')->delete($infoFretePagamento->imagem_path);
                }
                $imagemPath = $this->uploadArquivo($request, 'imagem', 'info_frete_pagamento');
                $dados['imagem'] = basename($imagemPath);
                $dados['imagem_path'] = $imagemPath;
            }

            if ($request->hasFile('icone')) {
                if ($infoFretePagamento->icone_path) {
                    Storage::disk('public')->delete($infoFretePagamento->icone_path);
                }
                $iconePath = $this->uploadArquivo($request, 'icone', 'info_frete_pagamento');
                $dados['icone'] = basename($iconePath);
                $dados['icone_path'] = $iconePath;
            }

            $infoFretePagamento->update($dados);

            if ($infoFretePagamento->imagem_path) {
                $infoFretePagamento->imagem_path = url("storage/{$infoFretePagamento->imagem_path}");
            }
            if ($infoFretePagamento->icone_path) {
                $infoFretePagamento->icone_path = url("storage/{$infoFretePagamento->icone_path}");
            }

            return response()->json($infoFretePagamento, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar informação de frete e pagamento'], 500);
        }
    }

    /**
     * Deleta uma informação de frete e pagamento.
     *
     * Remove uma informação de frete e pagamento específica e seus arquivos associados do armazenamento.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID da informação de frete e pagamento.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Informação de frete e pagamento deletada com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Informação de frete e pagamento não encontrada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar informação de frete e pagamento"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $infoFretePagamento = InfoFretePagamento::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            if ($infoFretePagamento->imagem_path) {
                Storage::disk('public')->delete($infoFretePagamento->imagem_path);
            }
            if ($infoFretePagamento->icone_path) {
                Storage::disk('public')->delete($infoFretePagamento->icone_path);
            }

            $infoFretePagamento->delete();

            return response()->json(['message' => 'Informação de frete e pagamento deletada com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar informação de frete e pagamento'], 500);
        }
    }
}
