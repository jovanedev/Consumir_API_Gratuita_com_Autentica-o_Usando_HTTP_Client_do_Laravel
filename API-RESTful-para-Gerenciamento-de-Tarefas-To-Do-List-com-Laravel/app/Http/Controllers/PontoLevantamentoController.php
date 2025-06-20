<?php

namespace App\Http\Controllers;

use App\Models\PontoLevantamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Pontos de Levantamento
 *
 * APIs para gerenciar pontos de levantamento associados a uma loja.
 */
class PontoLevantamentoController extends Controller
{
    /**
     * Listar pontos de levantamento
     *
     * Retorna uma lista de todos os pontos de levantamento associados à loja do usuário autenticado.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "nome_local": "Ponto Central",
     *       "estado": "SP",
     *       "cidade": "São Paulo",
     *       "bairro": "Centro",
     *       "rua": "Rua Exemplo",
     *       "numero": "123",
     *       "complemento": "Sala 1",
     *       "loja_id": 1,
     *       "created_at": "2025-05-28T15:51:00.000000Z",
     *       "updated_at": "2025-05-28T15:51:00.000000Z"
     *     }
     *   ]
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao listar os pontos de levantamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function index()
    {
        try {
            // Obter o ID da loja do usuário autenticado
            $loja_id = Auth::user()->loja_id;

            // Recuperar os pontos de levantamento da loja logada
            $pontos = PontoLevantamento::where('loja_id', $loja_id)->get();

            return response()->json([
                'success' => true,
                'data' => $pontos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar os pontos de levantamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir um ponto de levantamento
     *
     * Retorna os detalhes de um ponto de levantamento específico com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id string required O ID do ponto de levantamento. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "nome_local": "Ponto Central",
     *     "estado": "SP",
     *     "cidade": "São Paulo",
     *     "bairro": "Centro",
     *     "rua": "Rua Exemplo",
     *     "numero": "123",
     *     "complemento": "Sala 1",
     *     "loja_id": 1,
     *     "created_at": "2025-05-28T15:51:00.000000Z",
     *     "updated_at": "2025-05-28T15:51:00.000000Z"
     *   }
     * }
     * @responseError 404 {
     *   "error": "Ponto de levantamento não encontrado"
     * }
     * @responseError 403 {
     *   "error": "Você não tem permissão para visualizar este ponto de levantamento."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao buscar o ponto de levantamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function show(string $id)
    {
        try {
            // Buscar o ponto de levantamento pelo ID
            $ponto = PontoLevantamento::find($id);

            if (!$ponto) {
                return response()->json(['error' => 'Ponto de levantamento não encontrado'], 404);
            }

            // Verificar se o ponto de levantamento pertence à loja logada
            if ($ponto->loja_id !== Auth::user()->loja_id) {
                return response()->json(['error' => 'Você não tem permissão para visualizar este ponto de levantamento.'], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $ponto
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar o ponto de levantamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar um novo ponto de levantamento
     *
     * Cria um novo ponto de levantamento associado à loja do usuário autenticado.
     *
     * @authenticated
     * @bodyParam nome_local string required O nome do ponto de levantamento. Exemplo: Ponto Central
     * @bodyParam estado string O estado do ponto de levantamento. Exemplo: SP
     * @bodyParam cidade string A cidade do ponto de levantamento. Exemplo: São Paulo
     * @bodyParam bairro string O bairro do ponto de levantamento. Exemplo: Centro
     * @bodyParam rua string A rua do ponto de levantamento. Exemplo: Rua Exemplo
     * @bodyParam numero string O número do endereço. Exemplo: 123
     * @bodyParam complemento string Informações adicionais do endereço. Exemplo: Sala 1
     * @bodyParam loja_id integer required O ID da loja associada. Exemplo: 1
     * @response 201 {
     *   "success": true,
     *   "message": "Ponto de levantamento criado com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "nome_local": "Ponto Central",
     *     "estado": "SP",
     *     "cidade": "São Paulo",
     *     "bairro": "Centro",
     *     "rua": "Rua Exemplo",
     *     "numero": "123",
     *     "complemento": "Sala 1",
     *     "loja_id": 1,
     *     "created_at": "2025-05-28T15:51:00.000000Z",
     *     "updated_at": "2025-05-28T15:51:00.000000Z"
     *   }
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Validação falhou.",
     *   "errors": {
     *     "nome_local": ["O campo nome_local é obrigatório."],
     *     "loja_id": ["O campo loja_id deve ser um ID válido."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao criar o ponto de levantamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome_local' => 'required|string|max:255',
            'estado' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'rua' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:255',
            'complemento' => 'nullable|string|max:255',
            'loja_id' => 'required|exists:lojas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validação falhou.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Definir o ID da loja do usuário logado
            $request->merge(['loja_id' => Auth::user()->loja_id]);

            // Criar o ponto de levantamento
            $ponto = PontoLevantamento::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Ponto de levantamento criado com sucesso!',
                'data' => $ponto
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar o ponto de levantamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar um ponto de levantamento
     *
     * Atualiza os dados de um ponto de levantamento existente com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id string required O ID do ponto de levantamento. Exemplo: 1
     * @bodyParam nome_local string required O nome do ponto de levantamento. Exemplo: Ponto Central
     * @bodyParam estado string O estado do ponto de levantamento. Exemplo: SP
     * @bodyParam cidade string A cidade do ponto de levantamento. Exemplo: São Paulo
     * @bodyParam bairro string O bairro do ponto de levantamento. Exemplo: Centro
     * @bodyParam rua string A rua do ponto de levantamento. Exemplo: Rua Exemplo
     * @bodyParam numero string O número do endereço. Exemplo: 123
     * @bodyParam complemento string Informações adicionais do endereço. Exemplo: Sala 1
     * @response 200 {
     *   "success": true,
     *   "message": "Ponto de levantamento atualizado com sucesso!",
     *   "data": {
     *     "id": 1,
     *     "nome_local": "Ponto Central",
     *     "estado": "SP",
     *     "cidade": "São Paulo",
     *     "bairro": "Centro",
     *     "rua": "Rua Exemplo",
     *     "numero": "123",
     *     "complemento": "Sala 1",
     *     "loja_id": 1,
     *     "created_at": "2025-05-28T15:51:00.000000Z",
     *     "updated_at": "2025-05-28T15:51:00.000000Z"
     *   }
     * }
     * @responseError 404 {
     *   "error": "Ponto de levantamento não encontrado"
     * }
     * @responseError 403 {
     *   "error": "Você não tem permissão para editar este ponto de levantamento."
     * }
     * @responseError 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "nome_local": ["O campo nome_local é obrigatório."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao atualizar o ponto de levantamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function update(Request $request, string $id)
    {
        // Validação dos campos recebidos
        $request->validate([
            'nome_local' => 'required|string|max:255',
            'estado' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'rua' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:255',
            'complemento' => 'nullable|string|max:255',
        ]);

        // Busca o ponto de levantamento pelo ID
        $ponto = PontoLevantamento::find($id);

        // Se o ponto de levantamento não existir, retorna um erro
        if (!$ponto) {
            return response()->json(['error' => 'Ponto de levantamento não encontrado'], 404);
        }

        // Verificar se o ponto de levantamento pertence à loja logada
        if ($ponto->loja_id !== Auth::user()->loja_id) {
            return response()->json(['error' => 'Você não tem permissão para editar este ponto de levantamento.'], 403);
        }

        try {
            // Atualiza os dados do ponto de levantamento
            $ponto->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Ponto de levantamento atualizado com sucesso!',
                'data' => $ponto
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar o ponto de levantamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover um ponto de levantamento
     *
     * Remove um ponto de levantamento com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id string required O ID do ponto de levantamento. Exemplo: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Ponto de levantamento removido com sucesso!"
     * }
     * @responseError 404 {
     *   "error": "Ponto de levantamento não encontrado"
     * }
     * @responseError 403 {
     *   "error": "Você não tem permissão para remover este ponto de levantamento."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao remover o ponto de levantamento.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function destroy(string $id)
    {
        // Busca o ponto de levantamento pelo ID
        $ponto = PontoLevantamento::find($id);

        // Se o ponto de levantamento não existir, retorna um erro
        if (!$ponto) {
            return response()->json(['error' => 'Ponto de levantamento não encontrado'], 404);
        }

        // Verificar se o ponto de levantamento pertence à loja logada
        if ($ponto->loja_id !== Auth::user()->loja_id) {
            return response()->json(['error' => 'Você não tem permissão para remover este ponto de levantamento.'], 403);
        }

        try {
            // Deleta o ponto de levantamento
            $ponto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ponto de levantamento removido com sucesso!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover o ponto de levantamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
