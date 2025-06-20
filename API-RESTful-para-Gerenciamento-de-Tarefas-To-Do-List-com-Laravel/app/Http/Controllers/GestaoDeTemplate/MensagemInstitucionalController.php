<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\MensagemInstitucional;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * @group Gestão de Template - Mensagens Institucionais
 *
 * Endpoints para gerenciamento de mensagens institucionais associadas a templates e lojas.
 */
class MensagemInstitucionalController extends Controller
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
     * Lista todas as mensagens institucionais de um template.
     *
     * Retorna uma lista de mensagens institucionais associadas ao template especificado e à loja do usuário autenticado.
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
     *       "subtitulo": "Bem-vindo à nossa loja",
     *       "titulo": "Sobre Nós",
     *       "titulo_italico": true,
     *       "link": "https://exemplo.com/sobre",
     *       "botao": "Saiba Mais"
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar mensagens institucionais"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $mensagens = MensagemInstitucional::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            return response()->json($mensagens, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar mensagens institucionais'], 500);
        }
    }

    /**
     * Exibe uma mensagem institucional específica.
     *
     * Retorna os detalhes de uma mensagem institucional específica com base no ID do template e da mensagem.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID da mensagem institucional.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "subtitulo": "Bem-vindo à nossa loja",
     *   "titulo": "Sobre Nós",
     *   "titulo_italico": true,
     *   "link": "https://exemplo.com/sobre",
     *   "botao": "Saiba Mais"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Mensagem institucional não encontrada"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $mensagem = MensagemInstitucional::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            return response()->json($mensagem, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Mensagem institucional não encontrada'], 404);
        }
    }

    /**
     * Cria uma nova mensagem institucional.
     *
     * Cria uma nova mensagem institucional associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam subtitulo string nullable Subtítulo da mensagem (máx. 255 caracteres). Exemplo: Bem-vindo à nossa loja
     * @bodyParam titulo string required Título da mensagem (máx. 255 caracteres). Exemplo: Sobre Nós
     * @bodyParam titulo_italico boolean required Define se o título deve ser exibido em itálico. Exemplo: true
     * @bodyParam link string nullable URL associada à mensagem (máx. 255 caracteres). Exemplo: https://exemplo.com/sobre
     * @bodyParam botao string nullable Texto do botão (máx. 255 caracteres). Exemplo: Saiba Mais
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "subtitulo": "Bem-vindo à nossa loja",
     *   "titulo": "Sobre Nós",
     *   "titulo_italico": true,
     *   "link": "https://exemplo.com/sobre",
     *   "botao": "Saiba Mais"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "titulo": ["O campo título é obrigatório"],
     *     "titulo_italico": ["O campo título_italico é obrigatório"],
     *     "subtitulo": ["O campo subtítulo deve ser uma string"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar mensagem institucional"
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
                'subtitulo' => 'nullable|string|max:255',
                'titulo' => 'required|string|max:255',
                'titulo_italico' => 'required|boolean',
                'link' => 'nullable|string|max:255',
                'botao' => 'nullable|string|max:255',
            ]);

            $mensagem = MensagemInstitucional::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'subtitulo' => $request->subtitulo,
                'titulo' => $request->titulo,
                'titulo_italico' => $request->titulo_italico,
                'link' => $request->link,
                'botao' => $request->botao,
            ]);

            return response()->json($mensagem, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar mensagem institucional'], 500);
        }
    }

    /**
     * Atualiza uma mensagem institucional existente.
     *
     * Atualiza os dados de uma mensagem institucional específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID da mensagem institucional.
     * @return JsonResponse
     *
     * @bodyParam subtitulo string nullable Subtítulo da mensagem (máx. 255 caracteres). Exemplo: Bem-vindo à nossa loja
     * @bodyParam titulo string Título da mensagem (máx. 255 caracteres). Exemplo: Sobre Nós
     * @bodyParam titulo_italico boolean Define se o título deve ser exibido em itálico. Exemplo: true
     * @bodyParam link string nullable URL associada à mensagem (máx. 255 caracteres). Exemplo: https://exemplo.com/sobre
     * @bodyParam botao string nullable Texto do botão (máx. 255 caracteres). Exemplo: Saiba Mais
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "subtitulo": "Bem-vindo à nossa loja",
     *   "titulo": "Sobre Nós",
     *   "titulo_italico": true,
     *   "link": "https://exemplo.com/sobre",
     *   "botao": "Saiba Mais"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Mensagem institucional não encontrada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "titulo": ["O campo título deve ser uma string"],
     *     "titulo_italico": ["O campo título_italico deve ser um booleano"],
     *     "subtitulo": ["O campo subtítulo deve ser uma string"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar mensagem institucional"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $mensagem = MensagemInstitucional::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'subtitulo' => 'nullable|string|max:255',
                'titulo' => 'sometimes|required|string|max:255',
                'titulo_italico' => 'sometimes|required|boolean',
                'link' => 'nullable|string|max:255',
                'botao' => 'nullable|string|max:255',
            ]);

            $mensagem->update($request->only([
                'subtitulo',
                'titulo',
                'titulo_italico',
                'link',
                'botao',
            ]));

            return response()->json($mensagem, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar mensagem institucional'], 500);
        }
    }

    /**
     * Deleta uma mensagem institucional.
     *
     * Remove uma mensagem institucional específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID da mensagem institucional.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Mensagem institucional deletada com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Mensagem institucional não encontrada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar mensagem institucional"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $mensagem = MensagemInstitucional::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $mensagem->delete();

            return response()->json(['message' => 'Mensagem institucional deletada com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar mensagem institucional'], 500);
        }
    }
}
