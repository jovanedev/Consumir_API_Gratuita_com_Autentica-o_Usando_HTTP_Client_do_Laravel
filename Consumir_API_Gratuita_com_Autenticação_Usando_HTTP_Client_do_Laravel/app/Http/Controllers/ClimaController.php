<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

/**
 * Controlador para consultar dados climáticos usando a OpenWeatherMap API.
 *
 * @group Clima
 * Endpoints para consulta de dados climáticos.
 */
class ClimaController extends Controller
{
    /**
     * Obtém os dados climáticos atuais para uma cidade.
     *
     * Este endpoint consulta a OpenWeatherMap API para retornar informações climáticas
     * como temperatura, umidade e descrição do tempo para uma cidade especificada.
     *
     * @queryParam cidade string required Nome da cidade. Exemplo: Luanda
     * @response 200 {
     *     "error": false,
     *     "dados": {
     *         "cidade": "Luanda",
     *         "temperatura": 27.2,
     *         "umidade": 75,
     *         "descricao": "céu limpo"
     *     }
     * }
     * @response 404 {
     *     "error": true,
     *     "mensagem": "Erro da API: city not found",
     *     "status_code": 404
     * }
     * @response 500 {
     *     "error": true,
     *     "mensagem": "Chave da API não configurada."
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function obterClima(Request $request): JsonResponse
    {
        // Valida o parâmetro da cidade
        $request->validate([
            'cidade' => 'required|string|min:2',
        ]);

        $cidade = $request->query('cidade');
        $apiKey = config('services.openweathermap.api_key');

        // Verifica se a chave da API está configurada
        if (empty($apiKey)) {
            return response()->json([
                'error' => true,
                'mensagem' => 'Chave da API não configurada.',
            ], 500);
        }

        // Faz a requisição à OpenWeatherMap API
        $response = Http::timeout(10)->get('https://api.openweathermap.org/data/2.5/weather', [
            'q' => $cidade,
            'appid' => $apiKey,
            'units' => 'metric', // Temperatura em Celsius
            'lang' => 'pt_br',   // Descrição em português
        ]);

        // Trata erros da API
        if ($response->failed()) {
            $statusCode = $response->status();
            $mensagemErro = $response->json('message', 'Falha ao obter dados climáticos');

            return response()->json([
                'error' => true,
                'mensagem' => "Erro da API: $mensagemErro",
                'status_code' => $statusCode,
            ], $statusCode);
        }

        // Extrai dados relevantes da resposta
        $dados = $response->json();
        $dadosClima = [
            'cidade' => $dados['name'],
            'temperatura' => $dados['main']['temp'],
            'umidade' => $dados['main']['humidity'],
            'descricao' => $dados['weather'][0]['description'],
        ];

        return response()->json([
            'error' => false,
            'dados' => $dadosClima,
        ], 200);
    }
}
