<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\MostrarProduto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * @group Gestão de Template - Configurações de Exibição de Produtos
 *
 * Endpoints para gerenciamento de configurações de exibição de produtos associadas a templates e lojas.
 */
class MostrarProdutoController extends Controller
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
     * Lista todas as configurações de exibição de produtos de um template.
     *
     * Retorna uma lista de configurações de exibição de produtos associadas ao template especificado e à loja do usuário autenticado.
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
     *       "mostrar_calculadora_frete": true,
     *       "mostrar_parcelas": true,
     *       "mostrar_preco_desconto": true,
     *       "variacoes_como_botoes": false,
     *       "variacoes_cor_como_foto": false,
     *       "link_guia_medidas": "https://exemplo.com/guia-medidas",
     *       "mostrar_estoque": true,
     *       "mostrar_mensagem_ultima_unidade": true,
     *       "mensagem_ultima_unidade": "Última unidade disponível!",
     *       "descricao_largura_total": true,
     *       "permitir_comentarios_facebook": true,
     *       "facebook_perfil_id": "123456789",
     *       "titulo_produtos_alternativos": "Produtos Similares",
     *       "titulo_produtos_complementares": "Complete seu Look"
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar configurações de exibição de produtos"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $mostrarProdutos = MostrarProduto::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            return response()->json($mostrarProdutos, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar configurações de exibição de produtos'], 500);
        }
    }

    /**
     * Exibe uma configuração de exibição de produto específica.
     *
     * Retorna os detalhes de uma configuração de exibição de produto específica com base no ID do template e da configuração.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID da configuração de exibição de produto.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "mostrar_calculadora_frete": true,
     *   "mostrar_parcelas": true,
     *   "mostrar_preco_desconto": true,
     *   "variacoes_como_botoes": false,
     *   "variacoes_cor_como_foto": false,
     *   "link_guia_medidas": "https://exemplo.com/guia-medidas",
     *   "mostrar_estoque": true,
     *   "mostrar_mensagem_ultima_unidade": true,
     *   "mensagem_ultima_unidade": "Última unidade disponível!",
     *   "descricao_largura_total": true,
     *   "permitir_comentarios_facebook": true,
     *   "facebook_perfil_id": "123456789",
     *   "titulo_produtos_alternativos": "Produtos Similares",
     *   "titulo_produtos_complementares": "Complete seu Look"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Configuração de exibição de produto não encontrada"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $mostrarProduto = MostrarProduto::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            return response()->json($mostrarProduto, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Configuração de exibição de produto não encontrada'], 404);
        }
    }

    /**
     * Cria uma nova configuração de exibição de produto.
     *
     * Cria uma nova configuração de exibição de produto associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param Request $request A requisição HTTP.
     * @return JsonResponse
     *
     * @bodyParam mostrar_calculadora_frete boolean required Define se a calculadora de frete será exibida. Exemplo: true
     * @bodyParam mostrar_parcelas boolean required Define se as opções de parcelamento serão exibidas. Exemplo: true
     * @bodyParam mostrar_preco_desconto boolean required Define se o preço com desconto será exibido. Exemplo: true
     * @bodyParam variacoes_como_botoes boolean required Define se as variações serão exibidas como botões. Exemplo: false
     * @bodyParam variacoes_cor_como_foto boolean required Define se as variações de cor serão exibidas como fotos. Exemplo: false
     * @bodyParam link_guia_medidas string nullable URL do guia de medidas (máx. 255 caracteres). Exemplo: https://exemplo.com/guia-medidas
     * @bodyParam mostrar_estoque boolean required Define se o estoque será exibido. Exemplo: true
     * @bodyParam mostrar_mensagem_ultima_unidade boolean required Define se a mensagem de última unidade será exibida. Exemplo: true
     * @bodyParam mensagem_ultima_unidade string required_if:mostrar_mensagem_ultima_unidade,true Mensagem de última unidade (máx. 255 caracteres). Exemplo: Última unidade disponível!
     * @bodyParam descricao_largura_total boolean required Define se a descrição usará largura total. Exemplo: true
     * @bodyParam permitir_comentarios_facebook boolean required Define se comentários do Facebook serão permitidos. Exemplo: true
     * @bodyParam facebook_perfil_id string required_if:permitir_comentarios_facebook,true ID do perfil do Facebook (máx. 255 caracteres). Exemplo: 123456789
     * @bodyParam titulo_produtos_alternativos string required Título para produtos alternativos (máx. 255 caracteres). Exemplo: Produtos Similares
     * @bodyParam titulo_produtos_complementares string required Título para produtos complementares (máx. 255 caracteres). Exemplo: Complete seu Look
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "mostrar_calculadora_frete": true,
     *   "mostrar_parcelas": true,
     *   "mostrar_preco_desconto": true,
     *   "variacoes_como_botoes": false,
     *   "variacoes_cor_como_foto": false,
     *   "link_guia_medidas": "https://exemplo.com/guia-medidas",
     *   "mostrar_estoque": true,
     *   "mostrar_mensagem_ultima_unidade": true,
     *   "mensagem_ultima_unidade": "Última unidade disponível!",
     *   "descricao_largura_total": true,
     *   "permitir_comentarios_facebook": true,
     *   "facebook_perfil_id": "123456789",
     *   "titulo_produtos_alternativos": "Produtos Similares",
     *   "titulo_produtos_complementares": "Complete seu Look"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "mostrar_calculadora_frete": ["O campo mostrar_calculadora_frete é obrigatório"],
     *     "mensagem_ultima_unidade": ["O campo mensagem_ultima_unidade é obrigatório quando mostrar_mensagem_ultima_unidade é true"],
     *     "facebook_perfil_id": ["O campo facebook_perfil_id é obrigatório quando permitir_comentarios_facebook é true"],
     *     "link_guia_medidas": ["O campo link_guia_medidas deve ser uma URL válida"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar configuração de exibição de produto"
     * }
     */
    public function store($template_id, Request $request): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();

            $request->validate([
                'mostrar_calculadora_frete' => 'required|boolean',
                'mostrar_parcelas' => 'required|boolean',
                'mostrar_preco_desconto' => 'required|boolean',
                'variacoes_como_botoes' => 'required|boolean',
                'variacoes_cor_como_foto' => 'required|boolean',
                'link_guia_medidas' => 'nullable|string|max:255|url',
                'mostrar_estoque' => 'required|boolean',
                'mostrar_mensagem_ultima_unidade' => 'required|boolean',
                'mensagem_ultima_unidade' => 'required_if:mostrar_mensagem_ultima_unidade,true|string|max:255',
                'descricao_largura_total' => 'required|boolean',
                'permitir_comentarios_facebook' => 'required|boolean',
                'facebook_perfil_id' => 'required_if:permitir_comentarios_facebook,true|string|max:255',
                'titulo_produtos_alternativos' => 'required|string|max:255',
                'titulo_produtos_complementares' => 'required|string|max:255',
            ]);

            $mostrarProduto = MostrarProduto::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'mostrar_calculadora_frete' => $request->mostrar_calculadora_frete,
                'mostrar_parcelas' => $request->mostrar_parcelas,
                'mostrar_preco_desconto' => $request->mostrar_preco_desconto,
                'variacoes_como_botoes' => $request->variacoes_como_botoes,
                'variacoes_cor_como_foto' => $request->variacoes_cor_como_foto,
                'link_guia_medidas' => $request->link_guia_medidas,
                'mostrar_estoque' => $request->mostrar_estoque,
                'mostrar_mensagem_ultima_unidade' => $request->mostrar_mensagem_ultima_unidade,
                'mensagem_ultima_unidade' => $request->mensagem_ultima_unidade,
                'descricao_largura_total' => $request->descricao_largura_total,
                'permitir_comentarios_facebook' => $request->permitir_comentarios_facebook,
                'facebook_perfil_id' => $request->facebook_perfil_id,
                'titulo_produtos_alternativos' => $request->titulo_produtos_alternativos,
                'titulo_produtos_complementares' => $request->titulo_produtos_complementares,
            ]);

            return response()->json($mostrarProduto, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar configuração de exibição de produto'], 500);
        }
    }

    /**
     * Atualiza uma configuração de exibição de produto existente.
     *
     * Atualiza os dados de uma configuração de exibição de produto específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID da configuração de exibição de produto.
     * @return JsonResponse
     *
     * @bodyParam mostrar_calculadora_frete boolean Define se a calculadora de frete será exibida. Exemplo: true
     * @bodyParam mostrar_parcelas boolean Define se as opções de parcelamento serão exibidas. Exemplo: true
     * @bodyParam mostrar_preco_desconto boolean Define se o preço com desconto será exibido. Exemplo: true
     * @bodyParam variacoes_como_botoes boolean Define se as variações serão exibidas como botões. Exemplo: false
     * @bodyParam variacoes_cor_como_foto boolean Define se as variações de cor serão exibidas como fotos. Exemplo: false
     * @bodyParam link_guia_medidas string nullable URL do guia de medidas (máx. 255 caracteres). Exemplo: https://exemplo.com/guia-medidas
     * @bodyParam mostrar_estoque boolean Define se o estoque será exibido. Exemplo: true
     * @bodyParam mostrar_mensagem_ultima_unidade boolean Define se a mensagem de última unidade será exibida. Exemplo: true
     * @bodyParam mensagem_ultima_unidade string required_if:mostrar_mensagem_ultima_unidade,true Mensagem de última unidade (máx. 255 caracteres). Exemplo: Última unidade disponível!
     * @bodyParam descricao_largura_total boolean Define se a descrição usará largura total. Exemplo: true
     * @bodyParam permitir_comentarios_facebook boolean Define se comentários do Facebook serão permitidos. Exemplo: true
     * @bodyParam facebook_perfil_id string required_if:permitir_comentarios_facebook,true ID do perfil do Facebook (máx. 255 caracteres). Exemplo: 123456789
     * @bodyParam titulo_produtos_alternativos string Título para produtos alternativos (máx. 255 caracteres). Exemplo: Produtos Similares
     * @bodyParam titulo_produtos_complementares string Título para produtos complementares (máx. 255 caracteres). Exemplo: Complete seu Look
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "mostrar_calculadora_frete": true,
     *   "mostrar_parcelas": true,
     *   "mostrar_preco_desconto": true,
     *   "variacoes_como_botoes": false,
     *   "variacoes_cor_como_foto": false,
     *   "link_guia_medidas": "https://exemplo.com/guia-medidas",
     *   "mostrar_estoque": true,
     *   "mostrar_mensagem_ultima_unidade": true,
     *   "mensagem_ultima_unidade": "Última unidade disponível!",
     *   "descricao_largura_total": true,
     *   "permitir_comentarios_facebook": true,
     *   "facebook_perfil_id": "123456789",
     *   "titulo_produtos_alternativos": "Produtos Similares",
     *   "titulo_produtos_complementares": "Complete seu Look"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Configuração de exibição de produto não encontrada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "mostrar_calculadora_frete": ["O campo mostrar_calculadora_frete deve ser um booleano"],
     *     "mensagem_ultima_unidade": ["O campo mensagem_ultima_unidade é obrigatório quando mostrar_mensagem_ultima_unidade é true"],
     *     "facebook_perfil_id": ["O campo facebook_perfil_id é obrigatório quando permitir_comentarios_facebook é true"],
     *     "link_guia_medidas": ["O campo link_guia_medidas deve ser uma URL válida"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar configuração de exibição de produto"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();

            $mostrarProduto = MostrarProduto::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'mostrar_calculadora_frete' => 'sometimes|required|boolean',
                'mostrar_parcelas' => 'sometimes|required|boolean',
                'mostrar_preco_desconto' => 'sometimes|required|boolean',
                'variacoes_como_botoes' => 'sometimes|required|boolean',
                'variacoes_cor_como_foto' => 'sometimes|required|boolean',
                'link_guia_medidas' => 'nullable|string|max:255|url',
                'mostrar_estoque' => 'sometimes|required|boolean',
                'mostrar_mensagem_ultima_unidade' => 'sometimes|required|boolean',
                'mensagem_ultima_unidade' => 'required_if:mostrar_mensagem_ultima_unidade,true|string|max:255',
                'descricao_largura_total' => 'sometimes|required|boolean',
                'permitir_comentarios_facebook' => 'sometimes|required|boolean',
                'facebook_perfil_id' => 'required_if:permitir_comentarios_facebook,true|string|max:255',
                'titulo_produtos_alternativos' => 'sometimes|required|string|max:255',
                'titulo_produtos_complementares' => 'sometimes|required|string|max:255',
            ]);

            $mostrarProduto->update($request->only([
                'mostrar_calculadora_frete',
                'mostrar_parcelas',
                'mostrar_preco_desconto',
                'variacoes_como_botoes',
                'variacoes_cor_como_foto',
                'link_guia_medidas',
                'mostrar_estoque',
                'mostrar_mensagem_ultima_unidade',
                'mensagem_ultima_unidade',
                'descricao_largura_total',
                'permitir_comentarios_facebook',
                'facebook_perfil_id',
                'titulo_produtos_alternativos',
                'titulo_produtos_complementares',
            ]));

            return response()->json($mostrarProduto, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar configuração de exibição de produto'], 500);
        }
    }

    /**
     * Deleta uma configuração de exibição de produto.
     *
     * Remove uma configuração de exibição de produto específica associada ao template e à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID da configuração de exibição de produto.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Configuração de exibição de produto deletada com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Configuração de exibição de produto não encontrada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar configuração de exibição de produto"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $loja_id = $this->getLojaId();

            $mostrarProduto = MostrarProduto::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $mostrarProduto->delete();

            return response()->json(['message' => 'Configuração de exibição de produto deletada com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar configuração de exibição de produto'], 500);
        }
    }
}
