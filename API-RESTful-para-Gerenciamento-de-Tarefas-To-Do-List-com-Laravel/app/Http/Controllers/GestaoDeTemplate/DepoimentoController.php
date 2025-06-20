<?php

namespace App\Http\Controllers\GestaoDeTemplate;

use App\Http\Controllers\Controller;
use App\Models\Depoimento;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

/**
 * @group Gestão de Template - Depoimentos
 *
 * Endpoints para gerenciamento de depoimentos associados a templates e lojas.
 */
class DepoimentoController extends Controller
{
    /**
     * Recupera o ID da loja do usuário autenticado.
     *
     * @return int|null O ID da loja ou null se não encontrado.
     */
    private function getLojaId(): ?int
    {
        $user = Auth::user();
        return $user->id_loja;
    }

    /**
     * Recupera o nome da pasta associada à loja.
     *
     * @return string|null O nome da pasta da loja ou null se não encontrado.
     */
    private function dadosLojaPasta(): ?string
    {
        $dados_loja = DB::table('lojas')->where('id', $loja_id)->first();
        return $dados_loja->pasta;
    }

    /**
     * Valida se o usuário possui uma loja associada.
     *
     * @return JsonResponse|null Resposta JSON com erro 403 se não houver loja associada, ou null se válido.
     */
    private function validateLojaId(): ?JsonResponse
    {
        $loja_id = $this->getLojaId();
        if (!$loja_id) {
            return response()->json(['error' => 'Usuário não possui loja associada'], 403);
        }
        return null;
    }

    /**
     * Faz o upload de um arquivo para o armazenamento.
     *
     * @param Request $request A requisição HTTP.
     * @param string $campo O nome do campo do arquivo na requisição.
     * @param string $pasta O diretório de destino no armazenamento.
     * @return string|null O caminho do arquivo armazenado ou null se não houver upload.
     */
    private function uploadArquivo(Request $request, string $campo, string $pasta): ?string
    {
        if ($request->hasFile($campo)) {
            $file = $request->file($campo);
            $nomeOriginal = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extensao = $file->getClientOriginalExtension();
            $nomeUnico = Str::slug($nomeOriginal) . '-' . Str::random(10) . '.' . $extensao;

            $pastaloja = $this->dadosLojaPasta();
            $caminho = $pastaloja . "/assets/gestaoTemplate/{$pasta}";
            $arquivoPath = $file->storeAs($caminho, $nomeUnico, 'public');

            return $arquivoPath;
        }

        return null;
    }

    /**
     * Lista todos os depoimentos de um template.
     *
     * Retorna uma lista de depoimentos associados ao template especificado e à loja do usuário autenticado.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "loja_id": 1,
     *       "template_id": 1,
     *       "titulo": "Depoimento Exemplo",
     *       "descricao_italico": true,
     *       "imagem_path": "https://storage.exemplo.com/path/to/image.jpg",
     *       "nome": "João Silva",
     *       "descricao": "Excelente experiência de compra!"
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "error": "Erro ao listar depoimentos"
     * }
     */
    public function index($template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $depoimentos = Depoimento::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->get();

            $depoimentos->transform(function ($depoimento) {
                if ($depoimento->imagem_path) {
                    $depoimento->imagem_path = url("storage/{$depoimento->imagem_path}");
                }
                return $depoimento;
            });

            return response()->json($depoimentos, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao listar depoimentos'], 500);
        }
    }

    /**
     * Exibe um depoimento específico.
     *
     * Retorna os detalhes de um depoimento específico com base no ID do template e do depoimento.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do depoimento.
     * @return JsonResponse
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Depoimento Exemplo",
     *   "descricao_italico": true,
     *   "imagem_path": "https://storage.exemplo.com/path/to/image.jpg",
     *   "nome": "João Silva",
     *   "descricao": "Excelente experiência de compra!"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Depoimento não encontrado"
     * }
     */
    public function show($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $depoimento = Depoimento::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            if ($depoimento->imagem_path) {
                $depoimento->imagem_path = url("storage/{$depoimento->imagem_path}");
            }

            return response()->json($depoimento, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Depoimento não encontrado'], 404);
        }
    }

    /**
     * Cria um novo depoimento.
     *
     * Cria um novo depoimento associado ao template e à loja do usuário autenticado, com upload de imagem.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @return JsonResponse
     *
     * @bodyParam titulo string required O título do depoimento (máx. 255 caracteres). Exemplo: "Depoimento Exemplo"
     * @bodyParam descricao_italico boolean required Define se a descrição deve ser exibida em itálico. Exemplo: true
     * @bodyParam imagem file required A imagem do depoimento (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam nome string required O nome do autor do depoimento (máx. 255 caracteres). Exemplo: "João Silva"
     * @bodyParam descricao string required A descrição do depoimento (máx. 1000 caracteres). Exemplo: "Excelente experiência de compra!"
     *
     * @response 201 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "titulo": "Depoimento Exemplo",
     *   "descricao_italico": true,
     *   "imagem_path": "https://storage.exemplo.com/path/to/image.jpg",
     *   "nome": "João Silva",
     *   "descricao": "Excelente experiência de compra!"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "titulo": ["O campo título é obrigatório"],
     *     "imagem": ["O campo imagem deve ser um arquivo do tipo: jpg, jpeg, png, webp"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar depoimento"
     * }
     */
    public function store(Request $request, $template_id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $request->validate([
                'titulo' => 'required|string|max:255',
                'descricao_italico' => 'required|boolean',
                'imagem' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
                'nome' => 'required|string|max:255',
                'descricao' => 'required|string|max:1000',
            ]);

            $imagemPath = $this->uploadArquivo($request, 'imagem', 'depoimentos');

            $depoimento = Depoimento::create([
                'loja_id' => $loja_id,
                'template_id' => $template_id,
                'titulo' => $request->titulo,
                'descricao_italico' => $request->descricao_italico,
                'imagem' => basename($imagemPath),
                'imagem_path' => $imagemPath,
                'nome' => $request->nome,
                'descricao' => $request->descricao,
            ]);

            if ($depoimento->imagem_path) {
                $depoimento->imagem_path = url("storage/{$depoimento->imagem_path}");
            }

            return response()->json($depoimento, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar depoimento'], 500);
        }
    }

    /**
     * Atualiza um depoimento existente.
     *
     * Atualiza os dados de um depoimento específico, incluindo a possibilidade de substituir a imagem.
     *
     * @authenticated
     * @param Request $request A requisição HTTP.
     * @param int $template_id O ID do template.
     * @param int $id O ID do depoimento.
     * @return JsonResponse
     *
     * @bodyParam cliente_id integer O ID do cliente associado (deve existir na tabela clientes). Exemplo: 1
     * @bodyParam titulo string O título do depoimento (máx. 255 caracteres). Exemplo: "Depoimento Atualizado"
     * @bodyParam descricao_italico boolean Define se a descrição deve ser exibida em itálico. Exemplo: true
     * @bodyParam imagem file A nova imagem do depoimento (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam nome string O nome do autor do depoimento (máx. 255 caracteres). Exemplo: "João Silva"
     * @bodyParam descricao string A descrição do depoimento (máx. 1000 caracteres). Exemplo: "Excelente experiência de compra!"
     *
     * @response 200 {
     *   "id": 1,
     *   "loja_id": 1,
     *   "template_id": 1,
     *   "cliente_id": 1,
     *   "titulo": "Depoimento Atualizado",
     *   "descricao_italico": true,
     *   "imagem_path": "https://storage.exemplo.com/path/to/new_image.jpg",
     *   "nome": "João Silva",
     *   "descricao": "Excelente experiência de compra!"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Depoimento não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "cliente_id": ["O campo cliente_id deve existir"],
     *     "titulo": ["O campo título deve ser uma string"]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar depoimento"
     * }
     */
    public function update(Request $request, $template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $depoimento = Depoimento::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            $request->validate([
                'cliente_id' => 'sometimes|required|exists:clientes,id',
                'titulo' => 'sometimes|required|string|max:255',
                'descricao_italico' => 'sometimes|required|boolean',
                'imagem' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'nome' => 'sometimes|required|string|max:255',
                'descricao' => 'sometimes|required|string|max:1000',
            ]);

            $dados = $request->only([
                'cliente_id',
                'titulo',
                'descricao_italico',
                'nome',
                'descricao',
            ]);

            if ($request->hasFile('imagem')) {
                if ($depoimento->imagem_path) {
                    Storage::disk('public')->delete($depoimento->imagem_path);
                }
                $imagemPath = $this->uploadArquivo($request, 'imagem', 'depoimentos');
                $dados['imagem'] = basename($imagemPath);
                $dados['imagem_path'] = $imagemPath;
            }

            $depoimento->update($dados);

            if ($depoimento->imagem_path) {
                $depoimento->imagem_path = url("storage/{$depoimento->imagem_path}");
            }

            return response()->json($depoimento, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar depoimento'], 500);
        }
    }

    /**
     * Deleta um depoimento.
     *
     * Remove um depoimento específico e sua imagem associada do armazenamento.
     *
     * @authenticated
     * @param int $template_id O ID do template.
     * @param int $id O ID do depoimento.
     * @return JsonResponse
     *
     * @response 200 {
     *   "message": "Depoimento deletado com sucesso"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Depoimento não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao deletar depoimento"
     * }
     */
    public function destroy($template_id, $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            $depoimento = Depoimento::where('loja_id', $loja_id)
                ->where('template_id', $template_id)
                ->findOrFail($id);

            if ($depoimento->imagem_path) {
                Storage::disk('public')->delete($depoimento->imagem_path);
            }

            $depoimento->delete();

            return response()->json(['message' => 'Depoimento deletado com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao deletar depoimento'], 500);
        }
    }
}
