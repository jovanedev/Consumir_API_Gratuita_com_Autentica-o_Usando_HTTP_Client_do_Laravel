<?php

namespace Tests\Feature;

use App\Models\Tarefa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TarefaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pode_criar_tarefa()
    {
        $response = $this->postJson('/api/tarefas/criar', [
            'titulo' => 'Tarefa de Teste',
            'descricao' => 'Esta é uma tarefa de teste',
            'status' => 'pendente',
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['titulo' => 'Tarefa de Teste']);
    }

    public function test_pode_listar_tarefas()
    {
        Tarefa::factory()->count(3)->create();

        $response = $this->getJson('/api/tarefas');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_pode_filtrar_tarefas_por_status()
    {
        Tarefa::factory()->create(['titulo' => 'Tarefa Pendente', 'status' => 'pendente']);
        Tarefa::factory()->create(['titulo' => 'Tarefa Concluída', 'status' => 'concluida']);
        Tarefa::factory()->create(['titulo' => 'Tarefa em Andamento', 'status' => 'em_andamento']);

        $response = $this->getJson('/api/tarefas/filtrar?status=pendente');

        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonFragment(['status' => 'pendente', 'titulo' => 'Tarefa Pendente']);
    }

    public function test_retorna_erro_ao_filtrar_por_status_invalido()
    {
        Tarefa::factory()->create(['status' => 'pendente']);

        $response = $this->getJson('/api/tarefas/filtrar?status=invalid');

        $response->assertStatus(422)
                 ->assertJson([
                     'error' => [
                         'status' => ['O status deve ser pendente, em_andamento ou concluída']
                     ]
                 ]);
    }

    public function test_pode_atualizar_tarefa()
    {
        $tarefa = Tarefa::factory()->create();

        $response = $this->patchJson("/api/tarefas/atualizar/{$tarefa->id}", [
            'status' => 'concluida',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'concluida']);
    }

    public function test_pode_deletar_tarefa()
    {
        $tarefa = Tarefa::factory()->create();

        $response = $this->deleteJson("/api/tarefas/deletar/{$tarefa->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('tarefas', ['id' => $tarefa->id]);
    }

    public function test_retorna_404_ao_atualizar_tarefa_inexistente()
    {
        $response = $this->patchJson('/api/tarefas/atualizar/999', [
            'status' => 'concluida',
        ]);

        $response->assertStatus(404);
    }
}
