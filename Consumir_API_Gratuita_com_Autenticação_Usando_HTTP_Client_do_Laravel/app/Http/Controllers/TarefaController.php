<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTarefaRequest;
use App\Http\Requests\UpdateTarefaRequest;
use App\Models\Tarefa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class TarefaController extends Controller
{
    /**
     * Lista todas as tarefas.
     *
     * Retorna uma lista de todas as tarefas no banco de dados.
     *
     * @param Request $request A requisição HTTP.
     * @return JsonResponse
     *
     * @response 200 {
     *   [
     *     {
     *       "id": 1,
     *       "titulo": "Tarefa Exemplo",
     *       "descricao": "Descrição da tarefa",
     *       "status": "pendente",
     *       "created_at": "2025-06-20T14:00:00.000000Z",
     *       "updated_at": "2025-06-20T14:00:00.000000Z"
     *     }
     *   ]
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar tarefas"
     * }
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tasks = Tarefa::all();
            return response()->json($tasks, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar tarefas'], 500);
        }
    }

    /**
     * Filtra tarefas por status.
     *
     * Retorna uma lista de tarefas filtradas pelo status fornecido (pendente, em_andamento, concluída).
     *
     * @param Request $request A requisição HTTP.
     * @return JsonResponse
     *
     * @queryParam status string required Status para filtrar as tarefas (pendente, em_andamento, concluída). Exemplo: pendente
     *
     * @response 200 {
     *   [
     *     {
     *       "id": 1,
     *       "titulo": "Tarefa Exemplo",
     *       "descricao": "Descrição da tarefa",
     *       "status": "pendente",
     *       "created_at": "2025-06-20T14:00:00.000000Z",
     *       "updated_at": "2025-06-20T14:00:00.000000Z"
     *     }
     *   ]
     * }
     * @responseError 422 {
     *   "error": {
     *     "status": ["O status deve ser pendente, em_andamento ou concluída"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao filtrar tarefas"
     * }
     */
    public function filterByStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'status' => ['required', 'in:pendente,em_andamento,concluida'],
            ], [
                'status.required' => 'O status é obrigatório.',
                'status.in' => 'O status deve ser pendente, em_andamento ou concluída.',
            ]);

            $tasks = Tarefa::where('status', $request->status)->get();
            return response()->json($tasks, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao filtrar tarefas'], 500);
        }
    }

    /**
     * Cria uma nova tarefa.
     *
     * Cria uma nova tarefa com título e descrição.
     *
     * @param StoreTarefaRequest $request A requisição HTTP com validação.
     * @return JsonResponse
     *
     * @bodyParam titulo string required Título da tarefa (máx. 255 caracteres). Exemplo: Tarefa Exemplo
     * @bodyParam descricao string nullable Descrição da tarefa. Exemplo: Descrição detalhada
     * @bodyParam status string nullable Status da tarefa (pendente, em_andamento, concluída). Exemplo: pendente
     *
     * @response 201 {
     *   "id": 1,
     *   "titulo": "Tarefa Exemplo",
     *   "descricao": "Descrição detalhada",
     *   "status": "pendente",
     *   "created_at": "2025-06-20T14:00:00.000000Z",
     *   "updated_at": "2025-06-20T14:00:00.000000Z"
     * }
     * @responseError 422 {
     *   "error": {
     *     "titulo": ["O título é obrigatório"],
     *     "status": ["O status deve ser pendente, em_andamento ou concluída"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar tarefa"
     * }
     */
    public function store(StoreTarefaRequest $request): JsonResponse
    {
        try {
            $task = Tarefa::create($request->validated());
            return response()->json($task, 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar tarefa'], 500);
        }
    }

    /**
     * Atualiza uma tarefa existente.
     *
     * Atualiza o título, descrição ou status de uma tarefa específica.
     *
     * @param UpdateTarefaRequest $request A requisição HTTP com validação.
     * @param Tarefa $tarefa A tarefa a ser atualizada.
     * @return JsonResponse
     *
     * @bodyParam titulo string nullable Título da tarefa (máx. 255 caracteres). Exemplo: Tarefa Atualizada
     * @bodyParam descricao string nullable Descrição da tarefa. Exemplo: Nova descrição
     * @bodyParam status string nullable Status da tarefa (pendente, em_andamento, concluída). Exemplo: concluida
     *
     * @response 200 {
     *   "id": 1,
     *   "titulo": "Tarefa Atualizada",
     *   "descricao": "Nova descrição",
     *   "status": "concluida",
     *   "created_at": "2025-06-20T14:00:00.000000Z",
     *   "updated_at": "2025-06-20T14:01:00.000000Z"
     * }
     * @responseError 404 {
     *   "error": "Tarefa não encontrada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "titulo": ["O título não pode ter mais de 255 caracteres"],
     *     "status": ["O status deve ser pendente, em_andamento ou concluída"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar tarefa"
     * }
     */
    public function update(UpdateTarefaRequest $request, Tarefa $tarefa): JsonResponse
    {
        try {
            $tarefa->update($request->validated());
            return response()->json($tarefa, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar tarefa'], 500);
        }
    }

    /**
     * Deleta uma tarefa.
     *
     * Remove uma tarefa específica do banco de dados.
     *
     * @param Tarefa $tarefa A tarefa a ser deletada.
     * @return JsonResponse
     *
     * @response 204 {}
     * @responseError 404 {
     *   "error": "Tarefa não encontrada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar tarefa"
     * }
     */
    public function destroy(Tarefa $tarefa): JsonResponse
    {
        try {
            $tarefa->delete();
            return response()->json(null, 204);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar tarefa'], 500);
        }
    }
}
