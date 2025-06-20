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

Uso do Git

Faça commits frequentes com mensagens claras, por exemplo:
Adiciona migração da tabela tarefas
Implementa modelo Tarefa
Adiciona controlador e rotas da API
Implementa testes unitários
Adiciona documentação Scribe
Cria README com instruções



