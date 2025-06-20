########################################################################### API-RESTful-para-Gerenciamento-de-Tarefas-To-Do-List-com-Laravel ##############################################################################

API de Gerenciamento de Tarefas
Uma API RESTful construída com Laravel para gerenciar tarefas.
Requisitos

PHP 8.2+
Composer
MySQL
Laravel 11
Scribe (para documentação)

Instruções de Configuração

Configure o arquivo .env com as credenciais do banco de dados:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=todo_api
DB_USERNAME=root
DB_PASSWORD=


Execute as migrações:
php artisan migrate


Inicie o servidor local:
php artisan serve


Gere a documentação com Scribe:
php artisan scribe:generate



Testando a API
Use Postman ou curl para testar os endpoints.
Exemplos de Endpoints

Criar Tarefa:
curl -X POST http://localhost:8000/api/tarefas/criar -H "Content-Type: application/json" -d '{"titulo":"Nova Tarefa","descricao":"Descrição da tarefa","status":"pendente"}'


Listar Tarefas:
curl -X GET http://localhost:8000/api/tarefas


Filtrar Tarefas por Status:
curl -X GET http://localhost:8000/api/tarefas/filtrar?status=pendente


Atualizar Tarefa:
curl -X PATCH http://localhost:8000/api/tarefas/atualizar/1 -H "Content-Type: application/json" -d '{"status":"concluida"}'


Deletar Tarefa:
curl -X DELETE http://localhost:8000/api/tarefas/deletar/1



Executando Testes
php artisan test

Documentação da API
Acesse a documentação em:
http://localhost:8000/docs


####################################################################### Consumir_API_Gratuita_com_Autenticação_Usando_HTTP_Client_do_Laravels ####################################################################

Este projeto Laravel implementa uma API RESTful para gerenciamento de tarefas e consome a OpenWeatherMap API para fornecer dados climáticos, com destaque para cidades como Luanda, Lisboa, São Paulo, e Nova York.
Configuração da OpenWeatherMap API
1. Configurar a Chave da API

Use a chave fornecida: 41f2ac9d462dee529f4d1ca72279022b.
Adicione ao arquivo .env:OPENWEATHERMAP_API_KEY=41f2ac9d462dee529f4d1ca72279022b


Configure config/services.php:'openweathermap' => [
    'api_key' => env('OPENWEATHERMAP_API_KEY'),
],



2. Instalar Dependências
composer install

3. Gerar Documentação com Scribe
php artisan scribe:generate

Acesse em http://localhost:8000/docs.
4. Testar o Endpoint

Endpoint: GET /api/clima?cidade=NomeDaCidade
Exemplo: http://localhost:8000/api/clima?cidade=Luanda
Use Postman ou navegador.

Endpoint de Clima
GET /api/clima

Descrição: Retorna os dados climáticos atuais para uma cidade.
Parâmetros:
cidade (query, obrigatório): Nome da cidade (ex.: Luanda).


Exemplo de Requisição (Luanda):GET http://localhost:8000/api/clima?cidade=Luanda


Exemplo de Resposta (Sucesso):{
  "error": false,
  "dados": {
    "cidade": "Luanda",
    "temperatura": 27.2,
    "umidade": 75,
    "descricao": "céu limpo"
  }
}


Exemplo de Resposta (Erro):{
  "error": true,
  "mensagem": "Erro da API: city not found",
  "status_code": 404
}


################################################################<<<<<<<<<<<<<<<<<<<<<<<<<>>>>>>>>>>>>>>>>>>>>>>>>>>>>##############################################################################


Exemplo de Integração
Deixaei um Html simples exemplificando a a integração (clima.html) foi criada para consultar o clima de cidades como Luanda, Lisboa, São Paulo, e Nova York.
Como Usar:

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Clima</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h1 class="text-2xl font-bold mb-4 text-center">Consulta de Clima</h1>
        <div class="mb-4">
            <input id="cidade" type="text" placeholder="Digite a cidade (ex.: Luanda)"
                   class="w-full p-2 border rounded-md">
        </div>
        <button onclick="consultarClima()"
                class="w-full bg-blue-500 text-white p-2 rounded-md hover:bg-blue-600">
            Consultar
        </button>
        <div id="resultado" class="mt-4 text-center"></div>
    </div>

    <script>
        async function consultarClima() {
            const cidade = document.getElementById('cidade').value;
            const resultado = document.getElementById('resultado');

            if (!cidade) {
                resultado.innerHTML = '<p class="text-red-500">Por favor, digite uma cidade.</p>';
                return;
            }

            try {
                const response = await fetch(`http://localhost:8000/api/clima?cidade=${encodeURIComponent(cidade)}`);
                const data = await response.json();

                if (data.error) {
                    resultado.innerHTML = `<p class="text-red-500">Erro: ${data.mensagem}</p>`;
                    return;
                }

                const { cidade: nomeCidade, temperatura, umidade, descricao } = data.dados;
                resultado.innerHTML = `
                    <p class="text-lg"><strong>Cidade:</strong> ${nomeCidade}</p>
                    <p><strong>Temperatura:</strong> ${temperatura}°C</p>
                    <p><strong>Umidade:</strong> ${umidade}%</p>
                    <p><strong>Condição:</strong> ${descricao}</p>
                `;
            } catch (error) {
                resultado.innerHTML = '<p class="text-red-500">Erro ao consultar o clima: ${error.message}</p>';
            }
        }
    </script>
</body>
</html>


################################################################<<<<<<<<<<<<<<<<<<<<<<<<>>>>>>>>>>>>>>>>>>>>>>>>>>>>################################################################################

Digite uma cidade (ex.: Luanda) e clique em "Consultar".
Veja os resultados estilizados com Tailwind CSS.

Código: Consulte public/weather.html.

Notas: por favor observar!

A chave da API é armazenada no .env.
HTTP Client: Usa a facade Http do Laravel.
Documentação: Gerada com Scribe, acessível em /docs.
Cidades Testadas: Luanda, Lisboa, São Paulo, Nova York.





