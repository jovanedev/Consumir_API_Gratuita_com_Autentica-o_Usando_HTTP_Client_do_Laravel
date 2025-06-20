API RESTful para Gerenciamento de Tarefas e Consulta de Clima
Este projeto implementa duas APIs RESTful usando o framework Laravel 11:

API de Gerenciamento de Tarefas: Permite criar, listar, filtrar, atualizar e deletar tarefas em uma To-Do List.
API de Consulta de Clima: Consome a OpenWeatherMap API para fornecer dados climáticos de cidades como Luanda, Lisboa, São Paulo e Nova York.

Ambas as APIs são documentadas com a biblioteca Scribe e estão disponíveis em repositórios separados no GitHub.
Requisitos
Para executar este projeto, você precisará das seguintes ferramentas:

PHP: Versão 8.2 ou superior
Composer: Gerenciador de dependências para PHP
MySQL: Banco de dados para a API de Tarefas
Laravel: Versão 11
Scribe: Para geração de documentação automática da API (em vez de Swagger)
Git: Para clonar os repositórios
Postman ou curl: Para testar os endpoints da API
Chave da API OpenWeatherMap (fornecida abaixo para testes)

Instruções de Configuração
Siga os passos abaixo para configurar e executar os projetos localmente. Note que as APIs estão em repositórios separados, então você precisará clonar ambos se desejar usar as duas funcionalidades.
1. Clonar os Repositórios
API de Gerenciamento de Tarefas
Clone o repositório da API de Tarefas:
git clone https://github.com/jovanedev/API-RESTful-para-Gerenciamento-de-Tarefas-To-Do-List-com-Laravel.git
cd API-RESTful-para-Gerenciamento-de-Tarefas-To-Do-List-com-Laravel

API de Consulta de Clima
Clone o repositório da API de Clima:
git clone https://github.com/jovanedev/Consumir_API_Gratuita_com_Autentica-o_Usando_HTTP_Client_do_Laravel.git
cd Consumir_API_Gratuita_com_Autentica-o_Usando_HTTP_Client_do_Laravel


Nota: Você pode optar por configurar apenas uma das APIs, dependendo da sua necessidade. As instruções abaixo se aplicam a ambos os repositórios, com diferenças específicas destacadas.

2. Instalar Dependências
Em cada repositório, instale as dependências do PHP usando o Composer:
composer install

3. Configurar o Arquivo .env
Em cada repositório, crie um arquivo .env copiando o arquivo .env.example:
cp .env.example .env

Para a API de Tarefas
Edite o arquivo .env para configurar as credenciais do banco de dados:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=todo_api
DB_USERNAME=root
DB_PASSWORD=

Para a API de Clima
Edite o arquivo .env para configurar as credenciais do banco de dados (se necessário) e a chave da API OpenWeatherMap:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=todo_api
DB_USERNAME=root
DB_PASSWORD=

OPENWEATHERMAP_API_KEY=41f2ac9d462dee529f4d1ca72279022b


Nota: Substitua DB_USERNAME e DB_PASSWORD pelas credenciais do seu banco de dados MySQL. A chave da API OpenWeatherMap fornecida (41f2ac9d462dee529f4d1ca72279022b) é para testes. Para uso em produção, obtenha sua própria chave em OpenWeatherMap.

4. Configurar o Serviço OpenWeatherMap (Apenas para a API de Clima)
No repositório da API de Clima, adicione a configuração do serviço OpenWeatherMap no arquivo config/services.php:
'openweathermap' => [
    'api_key' => env('OPENWEATHERMAP_API_KEY'),
],

5. Executar as Migrações
Execute as migrações para criar as tabelas necessárias no banco de dados (necessário apenas para a API de Tarefas):
php artisan migrate


Nota: A API de Clima não requer migrações, pois não utiliza um banco de dados local.

6. Iniciar o Servidor Local
Em cada repositório, inicie o servidor de desenvolvimento do Laravel:
php artisan serve

O servidor estará disponível em http://localhost:8000. Se você estiver rodando ambos os projetos simultaneamente, altere a porta de um deles (ex.: php artisan serve --port=8001).
7. Gerar Documentação com Scribe
Gere a documentação da API usando o Scribe em cada repositório:
php artisan scribe:generate

Acesse a documentação em http://localhost:8000/docs (ou na porta correspondente).
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
curl -X PATCH http://localhost:8000/api/tarefas/atualizar/1 -H "Content-Type: application/json" -d '{"status":"concluida"}'




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

No repositório da API de Clima, certifique-se de que o arquivo public/clima.html existe. Se não, crie-o e cole o código acima.
Acesse http://localhost:8000/clima.html no navegador (ou na porta correspondente).
Digite o nome de uma cidade (ex.: Luanda) no campo de entrada.
Clique em "Consultar" para ver os resultados estilizados com Tailwind CSS.

O código está disponível em public/clima.html e utiliza JavaScript com fetch para consumir o endpoint /api/clima.
Notas Adicionais

Chave da API: A chave da OpenWeatherMap API (41f2ac9d462dee529f4d1ca72279022b) é armazenada no arquivo .env para segurança.
HTTP Client: A API de Clima utiliza a facade Http do Laravel para realizar requisições à OpenWeatherMap API.
Documentação: A documentação completa de ambos os endpoints está disponível em http://localhost:8000/docs, gerada pelo Scribe.
Cidades Testadas: Os endpoints foram testados com sucesso para as cidades de Luanda, Lisboa, São Paulo e Nova York.

Assinatura
Este projeto é de JovaniDev
