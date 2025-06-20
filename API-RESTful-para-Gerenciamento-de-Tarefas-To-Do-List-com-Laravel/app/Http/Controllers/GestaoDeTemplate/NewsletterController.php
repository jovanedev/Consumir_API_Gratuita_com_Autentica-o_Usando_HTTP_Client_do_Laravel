<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

/**
 * @group Gestão de Template - Newsletters
 *
 * Endpoints para gerenciamento de newsletters associadas a templates e lojas.
 */
class NewsletterController extends Controller
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
        $dados_loja = DB::table('lojas')->where('id', $this->getLojaId())->first();
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
     * Lista todas as newsletters de um template.
     *
     * Retorna uma lista de newsletters associadas ao template especificado e à loja do usuário autenticado.
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
     *       "usar_cores_newsletter": true,
     *       "cor_fundo": "#FFFFFF",
     *       "cor_texto": "#000000",
     *       "imagem": "newsletter-imagem.jpg",
     *       "imagem_path": "https://storage.exemplo.com/path/to/newsletter-imagem.jpg",
     *       "titulo": "Assine Nossa Newsletter",
     *       "descricao": "Receba novidades e promoções exclusivas!"
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar newsletters"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();

            $newsletters = Newsletter::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            $newsletters->transform(function ($newsletter) {
                if ($newsletter->imagem_path) {
                    $newsletter->imagem_path = url("storage/{$newsletter->imagem_path}");
                }
                return $newsletter;
            });

            return response()->json($newsletters, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar newsletters'], 500);
        }
    }

    /**
     * Exibe uma newsletter específica.
     *
     * Retorna os detalhes de uma newsletter específica com base no ID do template e da newsletter.
     *
     * @authenticated
     * @param int $id O ID da newsletter.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "aumentar_largura_tela": true,
     *   "usar_cores_newsletter": true,
     *   "cor_fundo": "#FFFFFF",
     *   "cor_texto": "#000000",
     *   "imagem": "newsletter-imagem.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/newsletter-imagem.jpg",
     *   "titulo": "Assine Nossa Newsletter",
     *   "descricao": "Receba novidades e promoções exclusivas!"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Newsletter não encontrada"
     * }
     */
    public function show($id, $template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();

            $newsletter = Newsletter::where('id', $id)
                ->where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->firstOrFail();

            if ($newsletter->imagem_path) {
                $newsletter->imagem_path = url("storage/{$newsletter->imagem_path}");
            }

            return response()->json($newsletter, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Newsletter não encontrada'], 404);
        }
    }

    /**
     * Cria uma nova newsletter.
     *
     * Cria uma nova newsletter associada ao template e à loja do usuário autenticado, com upload de imagem.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam aumentar_largura_tela boolean required Define se a largura da tela será aumentada. Exemplo: true
     * @bodyParam usar_cores_newsletter boolean required Define se as cores personalizadas da newsletter serão usadas. Exemplo: true
     * @bodyParam cor_fundo string nullable Cor de fundo em formato hexadecimal (ex.: #FFFFFF). Exemplo: #FFFFFF
     * @bodyParam cor_texto string nullable Cor do texto em formato hexadecimal (ex.: #000000). Exemplo: #000000
     * @bodyParam imagem file required Imagem da newsletter (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam titulo string required Título da newsletter (máx. 255 caracteres). Exemplo: Assine Nossa Newsletter
     * @bodyParam descricao string required Descrição da newsletter (máx. 1000 caracteres). Exemplo: Receba novidades e promoções exclusivas!
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "aumentar_largura_tela": true,
     *   "usar_cores_newsletter": true,
     *   "cor_fundo": "#FFFFFF",
     *   "cor_texto": "#000000",
     *   "imagem": "newsletter-imagem.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/newsletter-imagem.jpg",
     *   "titulo": "Assine Nossa Newsletter",
     *   "descricao": "Receba novidades e promoções exclusivas!"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "aumentar_largura_tela": ["O campo aumentar_largura_tela é obrigatório"],
     *     "cor_fundo": ["O campo cor_fundo deve ser um código hexadecimal válido"],
     *     "imagem": ["O campo imagem deve ser um arquivo do tipo: jpg, jpeg, png, webp"],
     *     "titulo": ["O campo título é obrigatório"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar newsletter"
     * }
     */
    public function store(Request $request, $template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $request->validate([
                'aumentar_largura_tela' => 'required|boolean',
                'usar_cores_newsletter' => 'required|boolean',
                'cor_fundo' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'cor_texto' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'imagem' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
                'titulo' => 'required|string|max:255',
                'descricao' => 'required|string|max:1000',
            ]);

            $loja_id = $this->getLojaId();

            $imagemPath = $this->uploadArquivo($request, 'imagem', 'newsletters');

            $newsletter = Newsletter::create([
                'template_id' => $template_id,
                'loja_id' => $loja_id,
                'aumentar_largura_tela' => $request->aumentar_largura_tela,
                'usar_cores_newsletter' => $request->usar_cores_newsletter,
                'cor_fundo' => $request->cor_fundo,
                'cor_texto' => $request->cor_texto,
                'imagem' => basename($imagemPath),
                'imagem_path' => $imagemPath,
                'titulo' => $request->titulo,
                'descricao' => $request->descricao,
            ]);

            if ($newsletter->imagem_path) {
                $newsletter->imagem_path = url("storage/{$newsletter->imagem_path}");
            }

            return response()->json($newsletter, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar newsletter'], 500);
        }
    }
    /**
     * Atualiza uma newsletter existente.
     *
     * Atualiza os dados de uma newsletter específica, com a opção de substituir a imagem.
     *
     * @authenticated
     * @url PUT /api/templates/{template_id}/newsletters/{id}
     * @urlParam template_id integer required O ID do template. Exemplo: 1
     * @urlParam id integer required O ID da newsletter. Exemplo: 1
     * @bodyParam aumentar_largura_tela boolean Define se a largura da tela será aumentada. Exemplo: true
     * @bodyParam usar_cores_newsletter boolean Define se as cores personalizadas da newsletter serão usadas. Exemplo: true
     * @bodyParam cor_fundo string nullable Cor de fundo em formato hexadecimal (ex.: #FFFFFF). Exemplo: #FFFFFF
     * @bodyParam cor_texto string nullable Cor do texto em formato hexadecimal (ex.: #000000). Exemplo: #000000
     * @bodyParam imagem file nullable Nova imagem da newsletter (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam titulo string Título da newsletter (máx. 255 caracteres). Exemplo: Assine Nossa Newsletter
     * @bodyParam descricao string Descrição da newsletter (máx. 1000 caracteres). Exemplo: Atualize suas preferências de newsletter!
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "aumentar_largura_tela": true,
     *   "usar_cores_newsletter": true,
     *   "cor_fundo": "#FFFFFF",
     *   "cor_texto": "#000000",
     *   "imagem": "newsletter-imagem-atualizada.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/newsletter-imagem-atualizada.jpg",
     *   "titulo": "Assine Nossa Newsletter",
     *   "descricao": "Atualize suas preferências de newsletter!"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Newsletter não encontrada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "aumentar_largura_tela": ["O campo aumentar_largura_tela deve ser um booleano"],
     *     "cor_fundo": ["O campo cor_fundo deve ser um código hexadecimal válido"],
     *     "imagem": ["O campo imagem deve ser um arquivo do tipo: jpg, jpeg, png, webp"],
     *     "titulo": ["O campo título deve ser uma string"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar newsletter"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();

            $newsletter = Newsletter::where('id', $id)
                ->where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->first();

            if (!$newsletter) {
                return response()->json(['error' => 'Newsletter não encontrada'], 404);
            }

            $request->validate([
                'aumentar_largura_tela' => 'sometimes|boolean',
                'usar_cores_newsletter' => 'sometimes|boolean',
                'cor_fundo' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'cor_texto' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'imagem' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'titulo' => 'sometimes|string|max:255',
                'descricao' => 'sometimes|string|max:1000',
            ]);

            $dados = $request->only([
                'aumentar_largura_tela',
                'usar_cores_newsletter',
                'cor_fundo',
                'cor_texto',
                'titulo',
                'descricao',
            ]);

            if ($request->hasFile('imagem')) {
                if ($newsletter->imagem_path) {
                    Storage::disk('public')->delete($newsletter->imagem_path);
                }

                $imagemPath = $this->uploadArquivo($request, 'imagem', 'newsletters');
                $dados['imagem'] = basename($imagemPath);
                $dados['imagem_path'] = $imagemPath;
            }

            $newsletter->update($dados);

            if ($newsletter->imagem_path) {
                $newsletter->imagem_path = url("storage/{$newsletter->imagem_path}");
            }

            return response()->json($newsletter, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar newsletter'], 500);
        }
    }

    /**
     * Deleta uma newsletter.
     *
     * Remove uma newsletter específica e sua imagem associada do armazenamento.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID da newsletter.
     * @return JsonResponse
     *
     * @response 200 {
     *   "mensagem": "Newsletter deletada com sucesso"
     * }
     * @responseError 403 {
     *   "Erro": "Usuário não possui loja associada ao"
     * }
     * @responseError 404 {
     *   "Erro": "Newsletter não encontrada foi"
     * }
     * @responseError 500 {
     *   "Erro": "Erro ao deletar newsletter"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
     {
         try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLoja_id();

            $newsletter = Newsletter::where('id', $id)
                ->where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            if ($newsletter->imagem_path) {
                Storage::disk('public')->delete($newsletter->imagem_path);
            }

            $newsletter->delete();

            return response()->json(['mensagem' => 'Newsletter deletada com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar newsletter'], 500);
        }
    }
}
