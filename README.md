API RESTful para Gerenciamento de Tarefas e Consulta de Clima
Este projeto Laravel implementa duas APIs RESTful:

API de Gerenciamento de Tarefas: Permite criar, listar, filtrar, atualizar e deletar tarefas em uma To-Do List.
API de Consulta de Clima: Consome a OpenWeatherMap API para fornecer dados climáticos de cidades como Luanda, Lisboa, São Paulo e Nova York.

Ambas as APIs utilizam o framework Laravel 11 e são documentadas com a biblioteca Scribe.
Requisitos
Para executar este projeto, você precisará das seguintes ferramentas:

PHP: Versão 8.2 ou superior
Composer: Gerenciador de dependências para PHP
MySQL: Banco de dados para a API de Tarefas
Laravel: Versão 11
Para geração de documentação automática da API usou-se o Scribe ao invés do Swagger
Use o Postman ou curl  para testar os endpoints da API
Chave da API OpenWeatherMap (fornecida abaixo para testes)

Instruções de Configuração
Siga os passos abaixo para configurar e executar o projeto localmente.
1. Clonar o Repositório
Clone o repositório do GitHub para sua máquina local:
git clone https://github.com/jovanedev/Consumir_API_Gratuita_com_Autentica-o_Usando_HTTP_Client_do_Laravel.git
cd Consumir_API_Gratuita_com_Autentica-o_Usando_HTTP_Client_do_Laravel

2. Instalar Dependências
Instale as dependências do PHP usando o Composer:
composer install

3. Configurar o Arquivo .env
Crie um arquivo .env na raiz do projeto copiando o arquivo .env.example:
cp .env.example .env

Edite o arquivo .env para configurar as credenciais do banco de dados e a chave da API OpenWeatherMap:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=todo_api
DB_USERNAME=root
DB_PASSWORD=

OPENWEATHERMAP_API_KEY=41f2ac9d462dee529f4d1ca72279022b


Nota: Substitua DB_USERNAME e DB_PASSWORD pelas credenciais do seu banco de dados MySQL. A chave da API OpenWeatherMap fornecida (41f2ac9d462dee529f4d1ca72279022b) é para testes. Para uso em produção, obtenha sua própria chave em OpenWeatherMap.

4. Configurar o Serviço OpenWeatherMap
Adicione a configuração do serviço OpenWeatherMap no arquivo config/services.php:
'openweathermap' => [
    'api_key' => env('OPENWEATHERMAP_API_KEY'),
],

5. Executar as Migrações
Execute as migrações para criar as tabelas necessárias no banco de dados:
php artisan migrate

6. Iniciar o Servidor Local
Inicie o servidor de desenvolvimento do Laravel:
php artisan serve

O servidor estará disponível em http://localhost:8000.
7. Gerar Documentação com Scribe
Gere a documentação da API usando o Scribe:
php artisan scribe:generate

Acesse a documentação em http://localhost:8000/docs.
Testando as APIs
Use ferramentas como Postman ou curl para testar os endpoints das APIs. Abaixo estão os detalhes de cada API.
API de Gerenciamento de Tarefas
Endpoints Disponíveis

Criar Tarefa

Método: POST

URL: http://localhost:8000/api/tarefas/criar

Corpo da Requisição:
{
    "titulo": "Nova Tarefa",
    "descricao": "Descrição da tarefa",
    "status": "pendente"
}


Exemplo com curl:
curl -X POST http://localhost:8000/api/tarefas/criar -H "Content-Type: application/json" -d '{"titulo":"Nova Tarefa","descricao":"Descrição da tarefa","status":"pendente"}'




Listar Tarefas

Método: GET

URL: http://localhost:8000/api/tarefas

Exemplo com curl:
curl -X GET http://localhost:8000/api/tarefas




Filtrar Tarefas por Status

Método: GET

URL: http://localhost:8000/api/tarefas/filtrar?status=pendente

Exemplo com curl:
curl -X GET http://localhost:8000/api/tarefas/filtrar?status=pendente




Atualizar Tarefa

Método: PATCH

URL: http://localhost:8000/api/tarefas/atualizar/{id}

Corpo da Requisição:
{
    "status": "concluida"
}


Exemplo com curl:
curl -X PATCH http://localhost:8000/api/tarefas/atualizar/1 -H "Content-Type: application/json" -d '{"status":"conclui
da"}'




Deletar Tarefa

Método: DELETE

URL: http://localhost:8000/api/tarefas/deletar/{id}

Exemplo com curl:
curl -X DELETE http://localhost:8000/api/tarefas/deletar/1





Executando Testes Automatizados
Para executar os testes da API de Tarefas:
php artisan test

API de Consulta de Clima
Endpoint Disponível

Método: GET

URL: http://localhost:8000/api/clima?cidade={NomeDaCidade}

Parâmetro: cidade (obrigatório, ex.: Luanda, Lisboa, São Paulo, Nova York)

Exemplo de Requisição:
curl -X GET http://localhost:8000/api/clima?cidade=Luanda


Exemplo de Resposta (Sucesso):
{
    "error": false,
    "dados": {
        "cidade": "Luanda",
        "temperatura": 27.2,
        "umidade": 75,
        "descricao": "céu limpo"
    }
}


Exemplo de Resposta (Erro):
{
    "error": true,
    "mensagem": "Erro da API: city not found",
    "status_code": 404
}



Interface de Consulta de Clima
Uma interface HTML simples (public/clima.html) foi criada para consultar o clima de cidades. O código completo do arquivo é mostrado abaixo:
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

Como Usar a Interface

Crie um arquivo html e cooque o código acima.
Abra em seguida e digite o nome de uma cidade (ex.: Luanda) no campo de entrada.
Clique em "Consultar" para ver os resultados estilizados com Tailwind CSS.

O código está disponível em public/clima.html e utiliza JavaScript com fetch para consumir o endpoint /api/clima.
Notas Adicionais

Chave da API: A chave da OpenWeatherMap API (41f2ac9d462dee529f4d1ca72279022b) é armazenada no arquivo .env para segurança.
HTTP Client: A API de Clima utiliza a facade Http do Laravel para realizar requisições à OpenWeatherMap API.
Documentação: A documentação completa de ambos os endpoints está disponível em http://localhost:8000/docs, gerada pelo Scribe.
Cidades Testadas: Os endpoints foram testados com sucesso para as cidades de Luanda, Lisboa, São Paulo e Nova York.
Segurança: Certifique-se de não expor a chave da API em repositórios públicos. Para projetos em produção, use uma chave própria.

Licença
Este projeto é licenciado sob a MIT License.
