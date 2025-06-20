<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\PopupPromocional;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

/**
 * @group Gestão de Template - Pop-ups Promocionais
 *
 * Endpoints para gerenciamento de pop-ups promocionais associados a templates e lojas.
 */
class PopupPromocionalController extends Controller
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
     * Lista todos os pop-ups promocionais de um template.
     *
     * Retorna uma lista de pop-ups promocionais associados ao template especificado e à loja do usuário autenticado.
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
     *       "mostrar_popup": true,
     *       "imagem": "popup-image.jpg",
     *       "imagem_path": "https://storage.exemplo.com/path/to/popup-image.jpg",
     *       "titulo": "Promoção Especial",
     *       "descricao": "Aproveite 20% de desconto na sua primeira compra!",
     *       "texto_botao": "Comprar Agora",
     *       "link_botao": "https://exemplo.com/promo",
     *       "permitir_inscricao_newsletter": true
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar pop-ups promocionais"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $popups = PopupPromocional::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            $popups->transform(function ($popup) {
                if ($popup->imagem_path) {
                    $popup->imagem_path = url("storage/{$popup->imagem_path}");
                }
                return $popup;
            });

            return response()->json($popups, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar pop-ups promocionais'], 500);
        }
    }

    /**
     * Exibe um pop-up promocional específico.
     *
     * Retorna os detalhes de um pop-up promocional específico com base no ID do template e do pop-up.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do pop-up promocional.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "mostrar_popup": true,
     *   "imagem": "popup-image.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/popup-image.jpg",
     *   "titulo": "Promoção Especial",
     *   "descricao": "Aproveite 20% de desconto na sua primeira compra!",
     *   "texto_botao": "Comprar Agora",
     *   "link_botao": "https://exemplo.com/promo",
     *   "permitir_inscricao_newsletter": true
     * }
     * @responseError 403 {
     *   مشابه "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Pop-up promocional não encontrado"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $popup = PopupPromocional::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            if ($popup->imagem_path) {
                $popup->imagem_path = url("storage/{$popup->imagem_path}");
            }

            return response()->json($popup, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Pop-up promocional não encontrado'], 404);
        }
    }

    /**
     * Cria um novo pop-up promocional.
     *
     * Cria um novo pop-up promocional associado ao template e à loja do usuário autenticado, com upload de imagem.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam mostrar_popup boolean required Define se o pop-up será exibido. Exemplo: true
     * @bodyParam imagem file required Imagem do pop-up (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam titulo string required Título do pop-up (máx. 255 caracteres). Exemplo: Promoção Especial
     * @bodyParam descricao string required Descrição do pop-up (máx. 1000 caracteres). Exemplo: Aproveite 20% de desconto na sua primeira compra!
     * @bodyParam texto_botao string required Texto do botão (máx. 255 caracteres). Exemplo: Comprar Agora
     * @bodyParam link_botao string required URL do botão (máx. 255 caracteres). Exemplo: https://exemplo.com/promo
     * @bodyParam permitir_inscricao_newsletter boolean required Define se a inscrição na newsletter é permitida. Exemplo: true
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "mostrar_popup": true,
     *   "imagem": "popup-image.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/popup-image.jpg",
     *   "titulo": "Promoção Especial",
     *   "descricao": "Aproveite 20% de desconto na sua primeira compra!",
     *   "texto_botao": "Comprar Agora",
     *   "link_botao": "https://exemplo.com/promo",
     *   "permitir_inscricao_newsletter": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "mostrar_popup": ["O campo mostrar_popup é obrigatório"],
     *     "imagem": ["O campo imagem deve ser um arquivo do tipo: jpg, jpeg, png, webp"],
     *     "titulo": ["O campo título é obrigatório"],
     *     "link_botao": ["O campo link_botao deve ser uma URL válida"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar pop-up promocional"
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
                'mostrar_popup' => 'required|boolean',
                'imagem' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
                'titulo' => 'required|string|max:255',
                'descricao' => 'required|string|max:1000',
                'texto_botao' => 'required|string|max:255',
                'link_botao' => 'required|string|max:255|url',
                'permitir_inscricao_newsletter' => 'required|boolean',
            ]);

            $imagemPath = $this->uploadArquivo($request, 'imagem', 'popups_promocionais');

            $popup = PopupPromocional::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'mostrar_popup' => $request->mostrar_popup,
                'imagem' => basename($imagemPath),
                'imagem_path' => $imagemPath,
                'titulo' => $request->titulo,
                'descricao' => $request->descricao,
                'texto_botao' => $request->texto_botao,
                'link_botao' => $request->link_botao,
                'permitir_inscricao_newsletter' => $request->permitir_inscricao_newsletter,
            ]);

            if ($popup->imagem_path) {
                $popup->imagem_path = url("storage/{$popup->imagem_path}");
            }

            return response()->json($popup, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar pop-up promocional'], 500);
        }
    }

    /**
     * Atualiza um pop-up promocional existente.
     *
     * Atualiza os dados de um pop-up promocional específico, com a opção de substituir a imagem.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID do pop-up promocional.
     * @return JsonResponse
     *
     * @bodyParam mostrar_popup boolean Define se o pop-up será exibido. Exemplo: true
     * @bodyParam imagem file nullable Nova imagem do pop-up (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam titulo string Título do pop-up (máx. 255 caracteres). Exemplo: Promoção Atualizada
     * @bodyParam descricao string Descrição do pop-up (máx. 1000 caracteres). Exemplo: Nova oferta com 30% de desconto!
     * @bodyParam texto_botao string Texto do botão (máx. 255 caracteres). Exemplo: Aproveitar Agora
     * @bodyParam link_botao string URL do botão (máx. 255 caracteres). Exemplo: https://exemplo.com/oferta
     * @bodyParam permitir_inscricao_newsletter boolean Define se a inscrição na newsletter é permitida. Exemplo: true
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "mostrar_popup": true,
     *   "imagem": "popup-image-updated.jpg",
     *   "imagem_path": "https://storage.exemplo.com/path/to/popup-image-updated.jpg",
     *   "titulo": "Promoção Atualizada",
     *   "descricao": "Nova oferta com 30% de desconto!",
     *   "texto_botao": "Aproveitar Agora",
     *   "link_botao": "https://exemplo.com/oferta",
     *   "permitir_inscricao_newsletter": true
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Pop-up promocional não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "mostrar_popup": ["O campo mostrar_popup deve ser um booleano"],
     *     "imagem": ["O campo imagem deve ser um arquivo do tipo: jpg, jpeg, png, webp"],
     *     "titulo": ["O campo título deve ser uma string"],
     *     "link_botao": ["O campo link_botao deve ser uma URL válida"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar pop-up promocional"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $popup = PopupPromocional::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'mostrar_popup' => 'sometimes|required|boolean',
                'imagem' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'titulo' => 'sometimes|required|string|max:255',
                'descricao' => 'sometimes|required|string|max:1000',
                'texto_botao' => 'sometimes|required|string|max:255',
                'link_botao' => 'sometimes|required|string|max:255|url',
                'permitir_inscricao_newsletter' => 'sometimes|required|boolean',
            ]);

            $dados = $request->only([
                'mostrar_popup',
                'titulo',
                'descricao',
                'texto_botao',
                'link_botao',
                'permitir_inscricao_newsletter',
            ]);

            if ($request->hasFile('imagem')) {
                if ($popup->imagem_path) {
                    Storage::disk('public')->delete($popup->imagem_path);
                }
                $imagemPath = $this->uploadArquivo($request, 'imagem', 'popups_promocionais');
                $dados['imagem'] = basename($imagemPath);
                $dados['imagem_path'] = $imagemPath;
            }

            $popup->update($dados);

            if ($popup->imagem_path) {
                $popup->imagem_path = url("storage/{$popup->imagem_path}");
            }

            return response()->json($popup, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar pop-up promocional'], 500);
        }
    }

    /**
     * Deleta um pop-up promocional.
     *
     * Remove um pop-up promocional específico e sua imagem associada do armazenamento.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do pop-up promocional.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Pop-up promocional deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Pop-up promocional não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar pop-up promocional"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();

            $popup = PopupPromocional::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            if ($popup->imagem_path) {
                Storage::disk('public')->delete($popup->imagem_path);
            }

            $popup->delete();

            return response()->json(['message' => 'Pop-up promocional deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar pop-up promocional'], 500);
        }
    }
}
