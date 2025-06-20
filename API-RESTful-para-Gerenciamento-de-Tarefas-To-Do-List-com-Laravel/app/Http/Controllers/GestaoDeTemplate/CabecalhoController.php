<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\Cabecalho;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * @group Gestão de Template - Cabeçalhos
 *
 * Endpoints para gerenciamento de cabeçalhos associados a templates e lojas.
 */
class CabecalhoController extends Controller
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
     * Lista todos os cabeçalhos de um template.
     *
     * Retorna uma lista de cabeçalhos associados ao template especificado e à loja do usuário autenticado.
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
     *       "cor_fundo": "#FFFFFF",
     *       "cor_texto_icones": "#000000",
     *       "tamanho_logo": "100px",
     *       "mostrar_idiomas": true,
     *       "cabecalho_em_celulares": {
     *         "layout": "compacto"
     *       },
     *       "cabecalho_em_computadores": {
     *         "layout": "expandido"
     *       },
     *       "barra_anuncio": {
     *         "texto": "Bem-vindo à nossa loja!"
     *       }
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao buscar cabeçalhos",
     *   "details": "Mensagem de erro detalhada"
     * }
     */
    public function index($template_id): JsonResponse
    {
        if ($error = $this->validateLojaId()) return $error;

        try {
            $loja_id = $this->getLojaId();
            $cabecalhos = Cabecalho::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            return response()->json($cabecalhos, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao buscar cabeçalhos', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Exibe um cabeçalho específico.
     *
     * Retorna os detalhes de um cabeçalho específico com base no ID do template e do cabeçalho.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do cabeçalho.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "cor_fundo": "#FFFFFF",
     *   "cor_texto_icones": "#000000",
     *   "tamanho_logo": "100px",
     *   "mostrar_idiomas": true,
     *   "cabecalho_em_celulares": {
     *     "layout": "compacto"
     *   },
     *   "cabecalho_em_computadores": {
     *     "layout": "expandido"
     *   },
     *   "barra_anuncio": {
     *     "texto": "Bem-vindo à nossa loja!"
     *   }
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Cabeçalho não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao buscar o cabeçalho",
     *   "details": "Mensagem de erro detalhada"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        if ($error = $this->validateLojaId()) return $error;

        try {
            $loja_id = $this->getLojaId();
            $cabecalho = Cabecalho::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            return response()->json($cabecalho, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Cabeçalho não encontrado'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao buscar o cabeçalho', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Cria um novo cabeçalho.
     *
     * Cria um novo cabeçalho associado ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam cor_fundo string required A cor de fundo do cabeçalho (formato hexadecimal, máx. 7 caracteres). Exemplo: "#FFFFFF"
     * @bodyParam cor_texto_icones string required A cor do texto e ícones (formato hexadecimal, máx. 7 caracteres). Exemplo: "#000000"
     * @bodyParam tamanho_logo string required O tamanho do logo (máx. 255 caracteres). Exemplo: "100px"
     * @bodyParam mostrar_idiomas boolean required Define se os idiomas devem ser exibidos. Exemplo: true
     * @bodyParam cabecalho_em_celulares object nullable Configurações do cabeçalho em dispositivos móveis. Exemplo: {"layout": "compacto"}
     * @bodyParam cabecalho_em_computadores object nullable Configurações do cabeçalho em computadores. Exemplo: {"layout": "expandido"}
     * @bodyParam barra_anuncio object nullable Configurações da barra de anúncio. Exemplo: {"texto": "Bem-vindo à nossa loja!"}
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "cor_fundo": "#FFFFFF",
     *   "cor_texto_icones": "#000000",
     *   "tamanho_logo": "100px",
     *   "mostrar_idiomas": true,
     *   "cabecalho_em_celulares": {
     *     "layout": "compacto"
     *   },
     *   "cabecalho_em_computadores": {
     *     "layout": "expandido"
     *   },
     *   "barra_anuncio": {
     *     "texto": "Bem-vindo à nossa loja!"
     *   }
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "cor_fundo": ["O campo cor_fundo é obrigatório"],
     *     "tamanho_logo": ["O campo tamanho_logo é obrigatório"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar cabeçalho",
     *   "details": "Mensagem de erro detalhada"
     * }
     */
    public function store(Request $request, $template_id): JsonResponse
    {
        if ($error = $this->validateLojaId()) return $error;

        try {
            $loja_id = $this->getLojaId();

            $request->validate([
                'cor_fundo' => 'required|string|max:7',
                'cor_texto_icones' => 'required|string|max:7',
                'tamanho_logo' => 'required|string|max:255',
                'mostrar_idiomas' => 'required|boolean',
                'cabecalho_em_celulares' => 'nullable|array',
                'cabecalho_em_computadores' => 'nullable|array',
                'barra_anuncio' => 'nullable|array',
            ]);

            $cabecalho = Cabecalho::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'cor_fundo' => $request->cor_fundo,
                'cor_texto_icones' => $request->cor_texto_icones,
                'tamanho_logo' => $request->tamanho_logo,
                'mostrar_idiomas' => $request->mostrar_idiomas,
                'cabecalho_em_celulares' => $request->cabecalho_em_celulares,
                'cabecalho_em_computadores' => $request->cabecalho_em_computadores,
                'barra_anuncio' => $request->barra_anuncio,
            ]);

            return response()->json($cabecalho, 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar cabeçalho', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Atualiza um cabeçalho existente.
     *
     * Atualiza os dados de um cabeçalho específico associado ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID do cabeçalho.
     * @return JsonResponse
     *
     * @bodyParam cor_fundo string A cor de fundo do cabeçalho (formato hexadecimal, máx. 7 caracteres). Exemplo: "#FFFFFF"
     * @bodyParam cor_texto_icones string A cor do texto e ícones (formato hexadecimal, máx. 7 caracteres). Exemplo: "#000000"
     * @bodyParam tamanho_logo string O tamanho do logo (máx. 255 caracteres). Exemplo: "100px"
     * @bodyParam mostrar_idiomas boolean Define se os idiomas devem ser exibidos. Exemplo: true
     * @bodyParam cabecalho_em_celulares object nullable Configurações do cabeçalho em dispositivos móveis. Exemplo: {"layout": "compacto"}
     * @bodyParam cabecalho_em_computadores object nullable Configurações do cabeçalho em computadores. Exemplo: {"layout": "expandido"}
     * @bodyParam barra_anuncio object nullable Configurações da barra de anúncio. Exemplo: {"texto": "Bem-vindo à nossa loja!"}
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "cor_fundo": "#FFFFFF",
     *   "cor_texto_icones": "#000000",
     *   "tamanho_logo": "100px",
     *   "mostrar_idiomas": true,
     *   "cabecalho_em_celulares": {
     *     "layout": "compacto"
     *   },
     *   "cabecalho_em_computadores": {
     *     "layout": "expandido"
     *   },
     *   "barra_anuncio": {
     *     "texto": "Bem-vindo à nossa loja!"
     *   }
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Cabeçalho não encontrado para atualização"
     * }
     * @responseError 422 {
     *   "error": {
     *     "cor_fundo": ["O campo cor_fundo deve ser uma string"],
     *     "tamanho_logo": ["O campo tamanho_logo é obrigatório"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar cabeçalho",
     *   "details": "Mensagem de erro detalhada"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        if ($error = $this->validateLojaId()) return $error;

        try {
            $loja_id = $this->getLojaId();

            $cabecalho = Cabecalho::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'cor_fundo' => 'sometimes|required|string|max:7',
                'cor_texto_icones' => 'sometimes|required|string|max:7',
                'tamanho_logo' => 'sometimes|required|string|max:255',
                'mostrar_idiomas' => 'sometimes|required|boolean',
                'cabecalho_em_celulares' => 'nullable|array',
                'cabecalho_em_computadores' => 'nullable|array',
                'barra_anuncio' => 'nullable|array',
            ]);

            $cabecalho->update($request->only([
                'cor_fundo',
                'cor_texto_icones',
                'tamanho_logo',
                'mostrar_idiomas',
                'cabecalho_em_celulares',
                'cabecalho_em_computadores',
                'barra_anuncio',
            ]));

            return response()->json($cabecalho, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Cabeçalho não encontrado para atualização'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar cabeçalho', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Deleta um cabeçalho.
     *
     * Remove um cabeçalho específico associado ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do cabeçalho.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Cabeçalho deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Cabeçalho não encontrado para exclusão"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar cabeçalho",
     *   "details": "Mensagem de erro detalhada"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        if ($error = $this->validateLojaId()) return $error;

        try {
            $loja_id = $this->getLojaId();

            $cabecalho = Cabecalho::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $cabecalho->delete();

            return response()->json(['message' => 'Cabeçalho deletado com sucesso'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Cabeçalho não encontrado para exclusão'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar cabeçalho', 'details' => $e->getMessage()], 500);
        }
    }
}
