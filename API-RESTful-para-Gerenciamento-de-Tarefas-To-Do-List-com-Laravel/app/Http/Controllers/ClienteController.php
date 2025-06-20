<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * @group Gestão de Clientes
 *
 * Endpoints para gerenciamento de clientes.
 */
class ClienteController extends Controller
{
    /**
     * Lista todos os clientes.
     *
     * Retorna uma lista de todos os clientes cadastrados no sistema.
     *
     * @authenticated
     * @return JsonResponse
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "user_id": 1,
     *       "nome": "João Silva",
     *       "data_nascimento": "1990-01-15",
     *       "genero": "masculino",
     *       "documento_tipo": "BI",
     *       "documento_numero": "123456789",
     *       "endereco_id": 1,
     *       "status": "ativo"
     *     }
     *   ]
     * }
     * @responseError 401 {
     *   "error": "Não autorizado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar clientes"
     * }
     */
    public function index(): JsonResponse
    {
        try {
            $clientes = Cliente::all();
            return response()->json(['data' => $clientes], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar clientes'], 500);
        }
    }

    /**
     * Exibe um cliente específico.
     *
     * Retorna os detalhes de um cliente com base no ID fornecido.
     *
     * @authenticated
     * @param int $id O ID do cliente.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "user_id": 1,
     *   "nome": "João Silva",
     *   "data_nascimento": "1990-01-15",
     *   "genero": "masculino",
     *   "documento_tipo": "BI",
     *   "documento_numero": "123456789",
     *   "endereco_id": 1,
     *   "status": "ativo"
     * }
     * @responseError 401 {
     *   "error": "Não autorizado"
     * }
     * @responseError 404 {
     *   "error": "Cliente não encontrado"
     * }
     */
    public function show($id): JsonResponse
    {
        try {
            $cliente = Cliente::findOrFail($id);
            return response()->json($cliente, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Cliente não encontrado'], 404);
        }
    }

    /**
     * Cria um novo cliente.
     *
     * Cria um novo cliente com base nos dados fornecidos.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @return JsonResponse
     *
     * @bodyParam user_id integer required O ID do usuário associado (deve existir na tabela users). Exemplo: 1
     * @bodyParam nome string required Nome do cliente (máx. 255 caracteres). Exemplo: João Silva
     * @bodyParam data_nascimento date required Data de nascimento do cliente (formato Y-m-d). Exemplo: 1990-01-15
     * @bodyParam genero string required Gênero do cliente (masculino, feminino, outro). Exemplo: masculino
     * @bodyParam documento_tipo string required Tipo de documento (BI, Passaporte, Outro). Exemplo: BI
     * @bodyParam documento_numero string required Número do documento (máx. 255 caracteres). Exemplo: 123456789
     * @bodyParam endereco_id integer required O ID do endereço associado (deve existir na tabela enderecos). Exemplo: 1
     * @bodyParam status string required Status do cliente (ativo, inativo). Exemplo: ativo
     *
     * @response 201 {
     *   "id": 1,
     *   "user_id": 1,
     *   "nome": "João Silva",
     *   "data_nascimento": "1990-01-15",
     *   "genero": "masculino",
     *   "documento_tipo": "BI",
     *   "documento_numero": "123456789",
     *   "endereco_id": 1,
     *   "status": "ativo"
     * }
     * @responseError 401 {
     *   "error": "Não autorizado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "user_id": ["O campo user_id é obrigatório"],
     *     "nome": ["O campo nome é obrigatório"],
     *     "data_nascimento": ["O campo data_nascimento deve ser uma data válida"],
     *     "genero": ["O campo genero deve ser masculino, feminino ou outro"],
     *     "documento_tipo": ["O campo documento_tipo deve ser BI, Passaporte ou Outro"],
     *     "documento_numero": ["O campo documento_numero é obrigatório"],
     *     "endereco_id": ["O campo endereco_id é obrigatório"],
     *     "status": ["O campo status deve ser ativo ou inativo"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar cliente"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'nome' => 'required|string|max:255',
                'data_nascimento' => 'required|date',
                'genero' => 'required|in:masculino,feminino,outro',
                'documento_tipo' => 'required|in:BI,Passaporte,Outro',
                'documento_numero' => 'required|string|max:255',
                'endereco_id' => 'required|exists:enderecos,id',
                'status' => 'required|in:ativo,inativo',
            ]);

            $cliente = Cliente::create([
                'user_id' => $request->user_id,
                'nome' => $request->nome,
                'data_nascimento' => $request->data_nascimento,
                'genero' => $request->genero,
                'documento_tipo' => $request->documento_tipo,
                'documento_numero' => $request->documento_numero,
                'endereco_id' => $request->endereco_id,
                'status' => $request->status,
            ]);

            return response()->json($cliente, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar cliente'], 500);
        }
    }

    /**
     * Atualiza um cliente existente.
     *
     * Atualiza os dados de um cliente específico com base no ID fornecido.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $id O ID do cliente.
     * @return JsonResponse
     *
     * @bodyParam user_id integer O ID do usuário associado (deve existir na tabela users). Exemplo: 2
     * @bodyParam nome string Nome do cliente (máx. 255 caracteres). Exemplo: Maria Souza
     * @bodyParam data_nascimento date Data de nascimento do cliente (formato Y-m-d). Exemplo: 1985-03-20
     * @bodyParam genero string Gênero do cliente (masculino, feminino, outro). Exemplo: feminino
     * @bodyParam documento_tipo string Tipo de documento (BI, Passaporte, Outro). Exemplo: Passaporte
     * @bodyParam documento_numero string Número do documento (máx. 255 caracteres). Exemplo: A987654321
     * @bodyParam endereco_id integer O ID do endereço associado (deve existir na tabela enderecos). Exemplo: 2
     * @bodyParam status string Status do cliente (ativo, inativo). Exemplo: inativo
     *
     * @response 200 {
     *   "id": 1,
     *   "user_id": 2,
     *   "nome": "Maria Souza",
     *   "data_nascimento": "1985-03-20",
     *   "genero": "feminino",
     *   "documento_tipo": "Passaporte",
     *   "documento_numero": "A987654321",
     *   "endereco_id": 2,
     *   "status": "inativo"
     * }
     * @responseError 401 {
     *   "error": "Não autorizado"
     * }
     * @responseError 404 {
     *   "error": "Cliente não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "user_id": ["O campo user_id deve existir na tabela users"],
     *     "nome": ["O campo nome deve ser uma string"],
     *     "data_nascimento": ["O campo data_nascimento deve ser uma data válida"],
     *     "genero": ["O campo genero deve ser masculino, feminino ou outro"],
     *     "documento_tipo": ["O campo documento_tipo deve ser BI, Passaporte ou Outro"],
     *     "documento_numero": ["O campo documento_numero deve ser uma string"],
     *     "endereco_id": ["O campo endereco_id deve existir na tabela enderecos"],
     *     "status": ["O campo status deve ser ativo ou inativo"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar cliente"
     * }
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $cliente = Cliente::findOrFail($id);

            $request->validate([
                'user_id' => 'sometimes|required|exists:users,id',
                'nome' => 'sometimes|required|string|max:255',
                'data_nascimento' => 'sometimes|required|date',
                'genero' => 'sometimes|required|in:masculino,feminino,outro',
                'documento_tipo' => 'sometimes|required|in:BI,Passaporte,Outro',
                'documento_numero' => 'sometimes|required|string|max:255',
                'endereco_id' => 'sometimes|required|exists:enderecos,id',
                'status' => 'sometimes|required|in:ativo,inativo',
            ]);

            $cliente->update($request->only([
                'user_id',
                'nome',
                'data_nascimento',
                'genero',
                'documento_tipo',
                'documento_numero',
                'endereco_id',
                'status',
            ]));

            return response()->json($cliente, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar cliente'], 500);
        }
    }

    /**
     * Deleta um cliente.
     *
     * Remove um cliente específico com base no ID fornecido.
     *
     * @authenticated
     * @param int $id O ID do cliente.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Cliente deletado com sucesso"
     * }
     * @responseError 401 {
     *   "error": "Não autorizado"
     * }
     * @responseError 404 {
     *   "error": "Cliente não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar cliente"
     * }
     */
    public function destroy($id): JsonResponse
    {
        try {
            $cliente = Cliente::findOrFail($id);
            $cliente->delete();

            return response()->json(['message' => 'Cliente deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar cliente'], 500);
        }
    }
}
