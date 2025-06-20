<?php

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\ClimaController;


    /*
    |--------------------------------------------------------------------------
    | Rotas da API
    |--------------------------------------------------------------------------
    */

    Route::get('/clima', [ClimaController::class, 'obterClima']);
?>
