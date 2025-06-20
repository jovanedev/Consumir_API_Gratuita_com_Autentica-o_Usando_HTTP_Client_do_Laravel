<?php

namespace App\Http\Controllers;

use App\Models\Email;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * @group Gestão de E-mails
 *
 * Endpoints para gerenciamento de e-mails associados a lojas.
 */
class EmailController extends Controller
{
    /**
     * Valida se o usuário possui uma loja associada.
     *
     * @return JsonResponse|null Resposta JSON com erro 403 se não houver loja associada, ou null se válido.
     */
    private function validateLojaId(): ?JsonResponse
    {
        $loja_id = Auth::user()->loja_id;
        if (!$loja_id) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não possui loja associada'
            ], 403);
        }
        return null;
    }

    /**
     * Lista todos os e-mails da loja autenticada.
     *
     * Retorna uma lista de e-mails associados à loja do usuário autenticado.
     *
     * @authenticated
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "loja_id": 1,
     *       "descricao": "E-mail de Boas-Vindas",
     *       "conteudo": "{\"subject\": \"Bem-vindo!\", \"body\": \"Obrigado por se cadastrar.\"}",
     *       "conteudo_html": "{\"html\": \"<h1>Bem-vindo!</h1><p>Obrigado por se cadastrar.</p>\"}",
     *       "status_conteudo_html": true,
     *       "tipo": "boas_vindas"
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao listar os e-mails.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function index(): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = Auth::user()->loja_id;

            $emails = Email::where('loja_id', $loja_id)->get();

            return response()->json([
                'success' => true,
                'data' => $emails
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar os e-mails.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe um e-mail específico.
     *
     * Retorna os detalhes de um e-mail com base no ID fornecido, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID do e-mail.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "descricao": "E-mail de Boas-Vindas",
     *     "conteudo": "{\"subject\": \"Bem-vindo!\", \"body\": \"Obrigado por se cadastrar.\"}",
     *     "conteudo_html": "{\"html\": \"<h1>Bem-vindo!</h1><p>Obrigado por se cadastrar.</p>\"}",
     *     "status_conteudo_html": true,
     *     "tipo": "boas_vindas"
     *   }
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para visualizar este e-mail."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "E-mail não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar o e-mail.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function show($id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = Auth::user()->loja_id;

            $email = Email::find($id);

            if (!$email) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-mail não encontrado.'
                ], 404);
            }

            if ($email->loja_id !== $loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para visualizar este e-mail.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $email
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar o e-mail.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria um novo e-mail.
     *
     * Cria um novo e-mail associado à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @return JsonResponse
     *
     * @bodyParam descricao string required Descrição do e-mail (máx. 255 caracteres). Exemplo: E-mail de Boas-Vindas
     * @bodyParam conteudo json required Conteúdo do e-mail em formato JSON. Exemplo: {"subject": "Bem-vindo!", "body": "Obrigado por se cadastrar."}
     * @bodyParam conteudo_html json required Conteúdo HTML do e-mail em formato JSON. Exemplo: {"html": "<h1>Bem-vindo!</h1><p>Obrigado por se cadastrar.</p>"}
     * @bodyParam status_conteudo_html boolean required Indica se o conteúdo HTML está ativo (true/false). Exemplo: true
     * @bodyParam tipo string required Tipo do e-mail (ativacao_conta, mudanca_senha, boas_vindas, cancelamento_compra, confirmacao_pagamento, confirmacao_compra, confirmacao_envio, carrinhos_abandonados). Exemplo: boas_vindas
     *
     * @response 201 {
     *   "success": true,
     *   "message": "E-mail criado com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "descricao": "E-mail de Boas-Vindas",
     *     "conteudo": "{\"subject\": \"Bem-vindo!\", \"body\": \"Obrigado por se cadastrar.\"}",
     *     "conteudo_html": "{\"html\": \"<h1>Bem-vindo!</h1><p>Obrigado por se cadastrar.</p>\"}",
     *     "status_conteudo_html": true,
     *     "tipo": "boas_vindas"
     *   }
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Validação falhou.",
     *   "errors": {
     *     "descricao": ["O campo descrição é obrigatório"],
     *     "conteudo": ["O campo conteúdo deve ser um JSON válido"],
     *     "conteudo_html": ["O campo conteúdo_html deve ser um JSON válido"],
     *     "status_conteudo_html": ["O campo status_conteudo_html deve ser true ou false"],
     *     "tipo": ["O campo tipo deve ser ativacao_conta, mudanca_senha, boas_vindas, cancelamento_compra, confirmacao_pagamento, confirmacao_compra, confirmacao_envio ou carrinhos_abandonados"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar o e-mail.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $validator = Validator::make($request->all(), [
                'descricao' => 'required|string|max:255',
                'conteudo' => 'required|json',
                'conteudo_html' => 'required|json',
                'status_conteudo_html' => 'required|in:false,true',
                'tipo' => 'required|in:ativacao_conta,mudanca_senha,boas_vindas,cancelamento_compra,confirmacao_pagamento,confirmacao_compra,confirmacao_envio,carrinhos_abandonados',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validação falhou.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $request->merge(['loja_id' => Auth::user()->loja_id]);

            $email = Email::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'E-mail criado com sucesso!',
                'data' => $email
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar o e-mail.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza um e-mail existente.
     *
     * Atualiza os dados de um e-mail específico, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $id O ID do e-mail.
     * @return JsonResponse
     *
     * @bodyParam descricao string nullable Descrição do e-mail (máx. 255 caracteres). Exemplo: E-mail de Boas-Vindas Atualizado
     * @bodyParam conteudo json nullable Conteúdo do e-mail em formato JSON. Exemplo: {"subject": "Bem-vindo Atualizado!", "body": "Obrigado por continuar conosco."}
     * @bodyParam conteudo_html json nullable Conteúdo HTML do e-mail em formato JSON. Exemplo: {"html": "<h1>Bem-vindo Atualizado!</h1><p>Obrigado por continuar conosco.</p>"}
     * @bodyParam status_conteudo_html boolean nullable Indica se o conteúdo HTML está ativo (true/false). Exemplo: false
     * @bodyParam tipo string nullable Tipo do e-mail (ativacao_conta, mudanca_senha, boas_vindas, cancelamento_compra, confirmacao_pagamento, confirmacao_compra, confirmacao_envio, carrinhos_abandonados). Exemplo: confirmacao_compra
     *
     * @response 200 {
     *   "success": true,
     *   "message": "E-mail atualizado com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "loja_id": 1,
     *     "descricao": "E-mail de Boas-Vindas Atualizado",
     *     "conteudo": "{\"subject\": \"Bem-vindo Atualizado!\", \"body\": \"Obrigado por continuar conosco.\"}",
     *     "conteudo_html": "{\"html\": \"<h1>Bem-vindo Atualizado!</h1><p>Obrigado por continuar conosco.</p>\"}",
     *     "status_conteudo_html": false,
     *     "tipo": "confirmacao_compra"
     *   }
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para editar este e-mail."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "E-mail não encontrado."
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Validação falhou.",
     *   "errors": {
     *     "descricao": ["O campo descrição deve ser uma string"],
     *     "conteudo": ["O campo conteúdo deve ser um JSON válido"],
     *     "conteudo_html": ["O campo conteúdo_html deve ser um JSON válido"],
     *     "status_conteudo_html": ["O campo status_conteudo_html deve ser true ou false"],
     *     "tipo": ["O campo tipo deve ser ativacao_conta, mudanca_senha, boas_vindas, cancelamento_compra, confirmacao_pagamento, confirmacao_compra, confirmacao_envio ou carrinhos_abandonados"]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar o e-mail.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = Auth::user()->loja_id;

            $email = Email::find($id);

            if (!$email) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-mail não encontrado.'
                ], 404);
            }

            if ($email->loja_id !== $loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar este e-mail.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'descricao' => 'nullable|string|max:255',
                'conteudo' => 'nullable|json',
                'conteudo_html' => 'nullable|json',
                'status_conteudo_html' => 'nullable|in:false,true',
                'tipo' => 'nullable|in:ativacao_conta,mudanca_senha,boas_vindas,cancelamento_compra,confirmacao_pagamento,confirmacao_compra,confirmacao_envio,carrinhos_abandonados',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validação falhou.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'E-mail atualizado com sucesso!',
                'data' => $email
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar o e-mail.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove um e-mail.
     *
     * Deleta um e-mail específico, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $id O ID do e-mail.
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "E-mail removido com sucesso!"
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Você não tem permissão para remover este e-mail."
     * }
     * @responseError 404 {
     *   "success": false,
     *   "message": "E-mail não encontrado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover o e-mail.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function destroy($id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = Auth::user()->loja_id;

            $email = Email::find($id);

            if (!$email) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-mail não encontrado.'
                ], 404);
            }

            if ($email->loja_id !== $loja_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para remover este e-mail.'
                ], 403);
            }

            $email->delete();

            return response()->json([
                'success' => true,
                'message' => 'E-mail removido com sucesso!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover o e-mail.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
