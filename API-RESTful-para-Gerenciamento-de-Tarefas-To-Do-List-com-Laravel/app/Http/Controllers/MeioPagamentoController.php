<?php

namespace App\Http\Controllers;

use App\Models\MeioPagamento;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group Gestão de Meios de Pagamento
 *
 * Endpoints para gerenciamento de meios de pagamento associados a lojas.
 */
class MeioPagamentoController extends Controller
{
    /**
     * Obtém o ID da loja do usuário autenticado.
     *
     * @return int|null O ID da loja ou null se não houver loja associada.
     */
    private function getLojaId(): ?int
    {
        $user = Auth::user();
        return $user ? $user->loja_id : null;
    }

    /**
     * Valida se o usuário está autenticado e possui uma loja associada.
     *
     * @return JsonResponse|null Resposta JSON com erro 401 ou 403 se inválido, ou null se permitido.
     */
    private function validateLojaId(): ?JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado.'
            ], 401);
        }
        $lojaId = $this->getLojaId();
        if (!$lojaId) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não possui loja associada.'
            ], 403);
        }
        return null;
    }

    /**
     * Faz o upload de um arquivo de logo.
     *
     * @param Request $request A requisição HTTP.
     * @param string $campo Nome do campo do arquivo.
     * @param string $pasta Subpasta para armazenamento.
     * @param int|null $item Índice para múltiplos registros (opcional).
     * @return string|null Caminho do arquivo armazenado ou null se não houver arquivo.
     * @throws \Exception Se o upload falhar.
     */
    private function uploadArquivo(Request $request, string $campo, string $pasta, $item = null): ?string
    {
        $file = $item !== null
            ? ($request->hasFile("{$campo}.{$item}") ? $request->file("{$campo}.{$item}") : null)
            : ($request->hasFile($campo) ? $request->file($campo) : null);

        if ($file) {
            $validator = Validator::make([$campo => $file], [
                $campo => 'file|mimes:jpg,jpeg,png,webp|max:2048'
            ]);
            if ($validator->fails()) {
                throw new \Exception('Arquivo inválido: ' . $validator->errors()->first($campo));
            }

            $nomeOriginal = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extensao = $file->getClientOriginalExtension();
            $nomeUnico = Str::slug($nomeOriginal) . '-' . Str::random(10) . '.' . $extensao;

            $caminho = "assets/meiosPagamento/{$pasta}";
            Storage::disk('public')->makeDirectory($caminho);
            $arquivoPath = $file->storeAs($caminho, $nomeUnico, 'public');

            return $arquivoPath;
        }

        return null;
    }

    /**
     * Lista todos os meios de pagamento da loja autenticada.
     *
     * Retorna uma lista de meios de pagamento associados à loja do usuário autenticado, com suas formas de pagamento relacionadas.
     *
     * @authenticated
     * @response 200 {
     *     "success": true,
     *     "message": "Meios de pagamento listados com sucesso.",
     *     "data": [
     *         {
     *             "id": 1,
     *             "loja_id": 1,
     *             "nome": "Cartão de Crédito",
     *             "logo_path": "https://example.com/storage/assets/meiosPagamento/logos/cartao-credito-abc123.jpg",
     *             "formas_pagamento": [
     *                 {"id": 1, "nome": "Visa"},
     *                 {"id": 2, "nome": "Mastercard"}
     *             ]
     *         }
     *     ]
     * }
     * @responseError 401 {
     *     "success": false,
     *     "message": "Não autorizado."
     * }
     * @responseError 403 {
     *     "success": false,
     *     "message": "Usuário não possui loja associada."
     * }
     * @responseError 500 {
     *     "success": false,
     *     "message": "Erro ao listar meios de pagamento.",
     *     "error": "Mensagem de erro detalhada."
     * }
     */
    public function index(): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $lojaId = $this->getLojaId();
            $meios = MeioPagamento::where('loja_id', $lojaId)
                ->with('formasPagamento')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Meios de pagamento listados com sucesso.',
                'data' => $meios
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar meios de pagamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe um meio de pagamento específico.
     *
     * Retorna os detalhes de um meio de pagamento com base no ID fornecido, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID do meio de pagamento. Exemplo: 1
     * @response 200 {
     *     "success": true,
     *     "message": "Meio de pagamento encontrado com sucesso.",
     *     "data": {
     *         "id": 1,
     *         "loja_id": 1,
     *         "nome": "Cartão de Crédito",
     *         "logo_path": "https://example.com/storage/assets/meiosPagamento/logos/cartao-credito-123.jpg",
     *         "formas_pagamento": [
     *             {"id": 1, "nome": "Visa"},
     *             {"id": 2, "nome": "Mastercard"}
     *         ]
     *     }
     * }
     * @responseError 401 {
     *     "success": false,
     *     "message": "Não autorizado."
     * }
     * @responseError 403 {
     *     "success": false,
     *     "message": "Você não tem permissão para visualizar este meio de pagamento."
     * }
     * @responseError 404 {
     *     "success": false,
     *     "message": "Meio de pagamento não encontrado."
     * }
     * @responseError 500 {
     *     "success": false,
     *     "message": "Erro ao buscar o meio de pagamento.",
     *     "error": "Mensagem de erro detalhada."
     * }
     */
    public function show(int $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $lojaId = $this->getLojaId();
            $meio = MeioPagamento::where('loja_id', $lojaId)
                ->with('formasPagamento')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Meio de pagamento encontrado com sucesso.',
                'data' => $meio
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Meio de pagamento não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar o meio de pagamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria um meio de pagamento.
     *
     * Cria um novo meio de pagamento associado à loja do usuário autenticado.
     *
     * @authenticated
     * @bodyParam nome string required Nome do meio de pagamento (máx. 100 caracteres, único por loja). Exemplo: Cartão de Crédito
     * @bodyParam logo file|null Logo do meio de pagamento (jpg, jpeg, png, webp, máx. 2MB). Exemplo: arquivo.jpg
     * @response 201 {
     *     "success": true,
     *     "message": "Meio de pagamento criado com sucesso.",
     *     "data": {
     *         "id": 1,
     *         "loja_id": 1,
     *         "nome": "Cartão de Crédito",
     *         "logo_path": "https://example.com/storage/assets/meiosPagamento/logos/cartao-credito-abc123.jpg"
     *     }
     * }
     * @responseError 401 {
     *     "success": false,
     *     "message": "Não autorizado."
     * }
     * @responseError 403 {
     *     "success": false,
     *     "message": "Usuário não possui loja associada."
     * }
     * @responseError 422 {
     *     "success": false,
     *     "message": "Validação falhou.",
     *     "errors": {
     *         "nome": ["O campo nome é obrigatório.", "O nome já está em uso nesta loja."],
     *         "logo": ["O arquivo deve ser uma imagem válida."]
     *     }
     * }
     * @responseError 500 {
     *     "success": false,
     *     "message": "Erro ao criar meio de pagamento.",
     *     "error": "Mensagem de erro detalhada."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $lojaId = $this->getLojaId();

            $rules = [
                'nome' => ['required', 'string', 'max:100', "unique:meios_pagamento,nome,NULL,id,loja_id,{$lojaId}"],
                'logo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validação falhou.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $logoPath = $this->uploadArquivo($request, 'logo', 'logos');
            $meio = MeioPagamento::create([
                'loja_id' => $lojaId,
                'nome' => $request->nome,
                'logo_path' => $logoPath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Meio de pagamento criado com sucesso.',
                'data' => $meio
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar meio de pagamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza um meio de pagamento existente.
     *
     * Atualiza os dados de um meio de pagamento específico, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID do meio de pagamento. Exemplo: 1
     * @bodyParam nome string|null Nome do meio de pagamento (máx. 100 caracteres, único por loja). Exemplo: Pix
     * @bodyParam logo file|null Novo logo do meio de pagamento (jpg, jpeg, png, webp, máx. 2MB). Exemplo: pix.jpg
     * @response 200 {
     *     "success": true,
     *     "message": "Meio de pagamento atualizado com sucesso.",
     *     "data": {
     *         "id": 1,
     *         "loja_id": 1,
         *         "nome": "Pix",
     *         "logo_path": "https://example.com/storage/assets/meiosPagamento/logos/pix-xyz789.jpg"
     *     }
     * }
     * @responseError 401 {
     *     "success": false,
     *     "message": "Não autorizado."
     * }
     * @responseError 403 {
     *     "success": false,
     *     "message": "Você não tem permissão para editar este meio de pagamento."
     * }
     * @responseError 404 {
     *     "success": false,
     *     "message": "Meio de pagamento não encontrado."
     * }
     * @responseError 422 {
     *     "success": false,
     *     "message": "Validação falhou.",
     *     "errors": {
     *         "nome": ["O nome já está em uso."],
     *         "logo": ["O arquivo deve ser uma imagem válida."]
     *     }
     * }
     * @responseError 500 {
     *     "success": false,
     *     "message": "Erro ao atualizar meio de pagamento.",
     *     "error": "Mensagem de erro detalhada."
     * }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $lojaId = $this->getLojaId();
            $meio = MeioPagamento::where('loja_id', $lojaId)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nome' => ['sometimes', 'required', 'string', 'max:100', "unique:meios_pagamento,nome,{$id},id,loja_id,{$lojaId}"],
                'logo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validação falhou.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dados = $request->only(['nome']);

            if ($request->hasFile('logo')) {
                if ($meio->logo_path && Storage::disk('public')->exists($meio->logo_path)) {
                    Storage::disk('public')->delete($meio->logo_path);
                }
                $logoPath = $this->uploadArquivo($request, 'logo', 'logos');
                $dados['logo_path'] = $logoPath;
            }

            $meio->update($dados);

            return response()->json([
                'success' => true,
                'message' => 'Meio de pagamento atualizado com sucesso.',
                'data' => $meio
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Meio de pagamento não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar meio de pagamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove um meio de pagamento.
     *
     * Deleta um meio de pagamento específico, restrito à loja do usuário autenticado.
     *
     * @authenticated
     * @urlParam id integer required O ID do meio de pagamento. Exemplo: 1
     * @response 200 {
     *     "success": true,
     *     "message": "Meio de pagamento removido com sucesso."
     * }
     * @responseError 401 {
     *     "success": false,
     *     "message": "Não autorizado."
     * }
     * @responseError 403 {
     *     "success": false,
     *     "message": "Você não tem permissão para remover este meio de pagamento."
     * }
     * @responseError 404 {
     *     "success": false,
     *     "message": "Meio de pagamento não encontrado."
     * }
     * @responseError 422 {
     *     "success": false,
     *     "message": "Não é possível remover o meio de pagamento devido a dependências.",
     *     "error": "O meio de pagamento está associado a formas de pagamento."
     * }
     * @responseError 500 {
     *     "success": false,
     *     "message": "Erro ao remover meio de pagamento.",
     *     "error": "Mensagem de erro detalhada."
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }

            $lojaId = $this->getLojaId();
            $meio = MeioPagamento::where('loja_id', $lojaId)->findOrFail($id);

            if ($meio->formasPagamento()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível remover o meio de pagamento devido a dependências.',
                    'error' => 'O meio de pagamento está associado a formas de pagamento.'
                ], 422);
            }

            if ($meio->logo_path && Storage::disk('public')->exists($meio->logo_path)) {
                Storage::disk('public')->delete($meio->logo_path);
            }

            $meio->delete();

            return response()->json([
                'success' => true,
                'message' => 'Meio de pagamento removido com sucesso.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Meio de pagamento não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover meio de pagamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}    
