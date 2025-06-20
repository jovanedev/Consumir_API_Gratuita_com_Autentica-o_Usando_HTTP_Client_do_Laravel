<?php

namespace App\Http\Controllers;

use App\Models\Loja;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * @group Gestão de Lojas
 *
 * Endpoints para gerenciamento de lojas.
 */
class LojaController extends Controller
{
    /**
     * Valida se o usuário está autenticado.
     *
     * @return JsonResponse|null Resposta JSON com erro 401 se não autenticado, ou null se válido.
     */
    private function validateAuth(): ?JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado.'
            ], 401);
        }
        return null;
    }

    /**
     * Lista todas as lojas.
     *
     * Retorna uma lista de todas as lojas registradas.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "message": "Lojas listadas com sucesso.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "nome": "Loja Exemplo",
     *       "pasta": "loja_exemplo_abc123",
     *       "descricao": "Uma loja de exemplo",
     *       "email": "contato@exemplo.com",
     *       "telefone": "(11) 98765-4321",
     *       "endereco": "Rua Exemplo, 123, São Paulo, SP",
     *       "logomarca": null,
     *       "categoria": "Varejo",
     *       "url_loja": "https://exemplo.com",
     *       "cor": "#FF0000",
     *       "cores_auxiliares": "#00FF00,#0000FF",
     *       "facebook": "https://facebook.com/exemplo",
     *       "instagram": "https://instagram.com/exemplo",
     *       "created_at": "2025-05-28T16:36:00.000000Z",
     *       "updated_at": "2025-05-28T16:36:00.000000Z"
     *     }
     *   ]
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao listar lojas.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function index(): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateAuth()) {
                return $errorResponse;
            }

            $lojas = Loja::all();

            return response()->json([
                'success' => true,
                'message' => 'Lojas listadas com sucesso.',
                'data' => $lojas
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar lojas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria uma nova loja.
     *
     * Cria uma nova loja com os dados fornecidos e estrutura de pastas associada.
     *
     * @authenticated
     * @bodyParam nome string required Nome da loja (máx. 255 caracteres). Exemplo: Loja Exemplo
     * @bodyParam descricao string|null Descrição da loja. Exemplo: Uma loja de exemplo
     * @bodyParam email string required Endereço de e-mail da loja (único e válido). Exemplo: contato@exemplo.com
     * @bodyParam telefone string|null Telefone da loja. Exemplo: (11) 98765-4321
     * @bodyParam endereco string|null Endereço da loja. Exemplo: Rua Exemplo, 123, São Paulo, SP
     * @bodyParam logomarca string|null Logomarca da loja (base64 ou URL). Exemplo: data:image/png;base64,...
     * @bodyParam categoria string|null Categoria da loja. Exemplo: Varejo
     * @bodyParam url_loja string|null URL da loja (formato válido). Exemplo: https://exemplo.com
     * @bodyParam cor string|null Cor principal em formato HEX. Exemplo: #FF0000
     * @bodyParam cores_auxiliares string|null Cores auxiliares em formato HEX, separadas por vírgula. Exemplo: #00FF00,#0000FF
     * @bodyParam facebook string|null URL do perfil do Facebook. Exemplo: https://facebook.com/exemplo
     * @bodyParam instagram string|null URL do perfil do Instagram. Exemplo: https://instagram.com/exemplo
     * @response 201 {
     *   "success": true,
     *   "message": "Loja criada com sucesso.",
     *   "loja_id": 1,
     *   "data": {
     *     "id": 1,
     *     "nome": "Loja Exemplo",
     *     "pasta": "loja_exemplo_abc123",
     *     "descricao": "Uma loja de exemplo",
     *     "email": "contato@exemplo.com",
     *     "telefone": "(11) 98765-4321",
     *     "endereco": "Rua Exemplo, 123, São Paulo, SP",
     *     "logomarca": null,
     *     "categoria": "Varejo",
     *     "url_loja": "https://exemplo.com",
     *     "cor": "#FF0000",
     *     "cores_auxiliares": "#00FF00,#0000FF",
     *     "facebook": "https://facebook.com/exemplo",
     *     "instagram": "https://instagram.com/exemplo",
     *     "created_at": "2025-05-28T16:36:00.000000Z",
     *     "updated_at": "2025-05-28T16:36:00.000000Z"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Erro de validação.",
     *   "errors": {
     *     "nome": ["O campo nome é obrigatório."],
     *     "email": ["O campo email deve ser um e-mail válido.", "O email já está em uso."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar loja.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateAuth()) {
                return $errorResponse;
            }

            $validator = Validator::make($request->all(), [
                'nome' => 'required|string|max:255',
                'descricao' => 'nullable|string',
                'email' => 'required|email|unique:lojas,email',
                'telefone' => 'nullable|string|max:20',
                'endereco' => 'nullable|string|max:255',
                'logomarca' => 'nullable|string',
                'categoria' => 'nullable|string|max:100',
                'url_loja' => 'nullable|url',
                'cor' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
                'cores_auxiliares' => 'nullable|regex:/^#[0-9A-Fa-f]{6}(,#[0-9A-Fa-f]{6})*$/',
                'facebook' => 'nullable|url',
                'instagram' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $nomeSanitizado = preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->nome);
            $nomePasta = 'loja_' . $nomeSanitizado . '_' . uniqid();

            $pastas = [
                "assets/gestaoTemplate/anuncios",
                "assets/gestaoTemplate/banner_estatico",
                "assets/gestaoTemplate/banner_promocional",
                "assets/gestaoTemplate/banner_rotativo",
                "assets/gestaoTemplate/banners_categorias",
                "assets/gestaoTemplate/banners_novidades",
                "assets/gestaoTemplate/depoimentos",
                "assets/gestaoTemplate/imagens_gt",
                "assets/gestaoTemplate/info_frete_pagamento",
                "assets/gestaoTemplate/marcas_gt",
                "assets/gestaoTemplate/newsletters",
                "assets/gestaoTemplate/popups_promocionais",
                "assets/gestaoTemplate/videos",
                "assets/meiosPagamento",
                "assets/produtos",
                "assets/css",
                "assets/js",
                "fonts",
            ];

            $loja = Loja::create([
                'nome' => $request->nome,
                'pasta' => $nomePasta,
                'descricao' => $request->descricao,
                'email' => $request->email,
                'telefone' => $request->telefone,
                'endereco' => $request->endereco,
                'logomarca' => $request->logomarca,
                'categoria' => $request->categoria,
                'url_loja' => $request->url_loja,
                'cor' => $request->cor,
                'cores_auxiliares' => $request->cores_auxiliares,
                'facebook' => $request->facebook,
                'instagram' => $request->instagram,
            ]);

            foreach ($pastas as $pasta) {
                Storage::disk('public')->makeDirectory("{$nomePasta}/{$pasta}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Loja criada com sucesso.',
                'loja_id' => $loja->id,
                'data' => $loja,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar loja.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe uma loja específica.
     *
     * Retorna os detalhes de uma loja com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id integer required O ID da loja. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Loja encontrada com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "nome": "Loja Exemplo",
     *     "pasta": "loja_exemplo_abc123",
     *     "descricao": "Uma loja de exemplo",
     *     "email": "contato@exemplo.com",
     *     "telefone": "(11) 98765-4321",
     *     "endereco": "Rua Exemplo, 123, São Paulo, SP",
     *     "logomarca": null,
     *     "categoria": "Varejo",
     *     "url_loja": "https://exemplo.com",
     *     "cor": "#FF0000",
     *     "cores_auxiliares": "#00FF00,#0000FF",
     *     "facebook": "https://facebook.com/exemplo",
     *     "instagram": "https://instagram.com/exemplo",
     *     "created_at": "2025-05-28T16:36:00.000000Z",
     *     "updated_at": "2025-05-28T16:36:00.000000Z"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Loja não encontrada."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar loja.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function show($id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateAuth()) {
                return $errorResponse;
            }

            $loja = Loja::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Loja encontrada com sucesso.',
                'data' => $loja
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Loja não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar loja.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza uma loja existente.
     *
     * Atualiza os dados de uma loja específica com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id integer required O ID da loja. Exemplo: 1
     * @bodyParam nome string required Nome da loja (máx. 255 caracteres). Exemplo: Loja Atualizada
     * @bodyParam descricao string|null Descrição da loja. Exemplo: Loja atualizada de exemplo
     * @bodyParam email string required Endereço de e-mail da loja (único e válido). Exemplo: novo@exemplo.com
     * @bodyParam telefone string|null Telefone da loja. Exemplo: (11) 91234-5678
     * @bodyParam endereco string|null Endereço da loja. Exemplo: Avenida Nova, 456, São Paulo, SP
     * @bodyParam logomarca string|null Logomarca da loja (base64 ou URL). Exemplo: data:image/png;base64,...
     * @bodyParam categoria string|null Categoria da loja. Exemplo: E-commerce
     * @bodyParam url_loja string|null URL da loja (formato válido). Exemplo: https://novoexemplo.com
     * @bodyParam cor string|null Cor principal em formato HEX. Exemplo: #00FF00
     * @bodyParam cores_auxiliares string|null Cores auxiliares em formato HEX, separadas por vírgula. Exemplo: #FF0000,#0000FF
     * @bodyParam facebook string|null URL do perfil do Facebook. Exemplo: https://facebook.com/novoexemplo
     * @bodyParam instagram string|null URL do perfil do Instagram. Exemplo: https://instagram.com/novoexemplo
     * @response 200 {
     *   "success": true,
     *   "message": "Loja atualizada com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "nome": "Loja Atualizada",
     *     "pasta": "loja_exemplo_abc123",
     *     "descricao": "Loja atualizada de exemplo",
     *     "email": "novo@exemplo.com",
     *     "telefone": "(11) 91234-5678",
     *     "endereco": "Avenida Nova, 456, São Paulo, SP",
     *     "logomarca": null,
     *     "categoria": "E-commerce",
     *     "url_loja": "https://novoexemplo.com",
     *     "cor": "#00FF00",
     *     "cores_auxiliares": "#FF0000,#0000FF",
     *     "facebook": "https://facebook.com/novoexemplo",
     *     "instagram": "https://instagram.com/novoexemplo",
     *     "created_at": "2025-05-28T16:36:00.000000Z",
     *     "updated_at": "2025-05-28T16:36:00.000000Z"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Loja não encontrada."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Erro de validação.",
     *   "errors": {
     *     "nome": ["O campo nome é obrigatório."],
     *     "email": ["O campo email deve ser um e-mail válido.", "O email já está em uso."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar loja.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateAuth()) {
                return $errorResponse;
            }

            $loja = Loja::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nome' => 'required|string|max:255',
                'descricao' => 'nullable|string',
                'email' => 'required|email|unique:lojas,email,' . $id,
                'telefone' => 'nullable|string|max:20',
                'endereco' => 'nullable|string|max:255',
                'logomarca' => 'nullable|string',
                'categoria' => 'nullable|string|max:100',
                'url_loja' => 'nullable|url',
                'cor' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
                'cores_auxiliares' => 'nullable|regex:/^#[0-9A-Fa-f]{6}(,#[0-9A-Fa-f]{6})*$/',
                'facebook' => 'nullable|url',
                'instagram' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $loja->update($request->only([
                'nome', 'descricao', 'email', 'telefone', 'endereco',
                'logomarca', 'categoria', 'url_loja', 'cor',
                'cores_auxiliares', 'facebook', 'instagram'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Loja atualizada com sucesso.',
                'data' => $loja,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Loja não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar loja.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove uma loja.
     *
     * Deleta uma loja específica com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id integer required O ID da loja. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Loja removida com sucesso."
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "Loja não encontrada."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover loja.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function destroy($id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateAuth()) {
                return $errorResponse;
            }

            $loja = Loja::findOrFail($id);

            // Exclui a pasta associada à loja
            if ($loja->pasta && Storage::disk('public')->exists($loja->pasta)) {
                Storage::disk('public')->deleteDirectory($loja->pasta);
            }

            $loja->delete();

            return response()->json([
                'success' => true,
                'message' => 'Loja removida com sucesso.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Loja não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover loja.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
