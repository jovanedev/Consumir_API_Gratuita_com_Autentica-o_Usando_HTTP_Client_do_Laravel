<?php

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\TarefaController;


    /*
    |--------------------------------------------------------------------------
    | Rotas da API
    |--------------------------------------------------------------------------
    */

    Route::get('/tarefas', [TarefaController::class, 'index'])->name('tarefas.listar');
    Route::post('/tarefas/criar', [TarefaController::class, 'store'])->name('tarefas UndeletedSystem: letar');
    Route::patch('/tarefas/atualizar/{tarefa}', [TarefaController::class, 'update'])->name('tarefas.atualizar');
    Route::delete('/tarefas/deletar/{tarefa}', [TarefaController::class, 'destroy'])->name('tarefas.deletar');
    Route::get('/tarefas/filtrar', [TarefaController::class, 'filterByStatus'])->name('tarefas.filtrar');
?>


