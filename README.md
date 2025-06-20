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



Exemplo de Integração
Uma interface web (weather.html) foi criada para consultar o clima de cidades como Luanda, Lisboa, São Paulo, e Nova York.
Como Usar:

Acesse http://localhost:8000/weather.html.
Digite uma cidade (ex.: Luanda) e clique em "Consultar".
Veja os resultados estilizados com Tailwind CSS.

Código: Consulte public/weather.html.

Notas: por favor observar!

A chave da API é armazenada no .env.
HTTP Client: Usa a facade Http do Laravel.
Documentação: Gerada com Scribe, acessível em /docs.
Cidades Testadas: Luanda, Lisboa, São Paulo, Nova York.





