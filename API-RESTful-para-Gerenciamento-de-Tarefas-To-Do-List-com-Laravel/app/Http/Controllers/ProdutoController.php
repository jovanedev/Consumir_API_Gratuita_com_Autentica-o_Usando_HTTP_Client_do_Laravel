<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Exception;

/**
 * @group Produtos
 *
 * APIs para gerenciar produtos associados a uma loja.
 */
class ProdutoController extends Controller
{
    /**
     * Recupera o loja_id do usuário autenticado
     *
     * @return int|null O ID da loja associada ao usuário autenticado ou null se não houver.
     */
    private function getLojaId(): ?int
    {
        $user = Auth::user();
        return $user->loja_id;
    }

    /**
     * Valida se o usuário possui uma loja associada
     *
     * @return JsonResponse|null Retorna uma resposta JSON de erro se não houver loja associada, ou null se válido.
     */
    private function validateLojaId(): ?JsonResponse
    {
        $loja_id = $this->getLojaId();
        if (!$loja_id) {
            return response()->json(['success' => false, 'message' => 'Usuário não possui loja associada'], 403);
        }
        return null;
    }

    /**
     * Faz o upload de um arquivo
     *
     * @param Request $request A requisição HTTP.
     * @param string $campo O nome do campo do arquivo no formulário.
     * @param string $pasta A pasta de destino no armazenamento.
     * @param string|null $index Índice para arquivos em array (opcional).
     * @return string|null O caminho do arquivo armazenado ou null se não houver arquivo.
     */
    private function uploadArquivo(Request $request, string $campo, string $pasta, ?string $index = null): ?string
    {
        $file = $index ? $request->file("{$campo}.{$index}") : $request->file($campo);
        if ($file) {
            $nomeOriginal = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extensao = $file->getClientOriginalExtension();
            $nomeUnico = Str::slug($nomeOriginal) . '-' . Str::random(10) . '.' . $extensao;

            $caminho = "assets/produtos/{$pasta}";
            $arquivoPath = $file->storeAs($caminho, $nomeUnico, 'public');

            return $arquivoPath;
        }
        return null;
    }

    /**
     * Listar produtos
     *
     * Retorna uma lista de todos os produtos associados à loja do usuário autenticado.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "nome": "Produto Exemplo",
     *       "descricao": "Descrição do produto",
     *       "referencia": "REF123",
     *       "codigo_unico_produto": "COD123",
     *       "codigo_barras": "7891234567890",
     *       "preco_compra": 50.00,
     *       "preco_venda": 100.00,
     *       "iva": 17,
     *       "gerir_stock": "sim",
     *       "preco_promocional": 80.00,
     *       "categoria_id": 1,
     *       "marca_id": 1,
     *       "fornecedor_id": 1,
     *       "loja_id": 1,
     *       "peso": 1.5,
     *       "largura": 10.0,
     *       "altura": 5.0,
     *       "comprimento": 15.0,
     *       "foto_capa_path": "http://example.com/storage/assets/produtos/fotos/exemplo.jpg",
     *       "imagens_paths": ["http://example.com/storage/assets/produtos/fotos/imagem1.jpg"],
     *       "video_url": "https://example.com/video",
     *       "status": "ativo",
     *       "destaque": true,
     *       "novidade": false,
     *       "produto_em_oferta": false,
     *       "frete_gratis": true,
     *       "prazo_envio": 3,
     *       "variacoes": false,
     *       "desconto_id": null,
     *       "visualizacoes": 100,
     *       "avaliacao_media": 4.5,
     *       "qtd_avaliacoes": 10,
     *       "created_at": "2025-05-28T15:54:00.000000Z",
     *       "updated_at": "2025-05-28T15:54:00.000000Z"
     *     }
     *   ]
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao listar os produtos.",
     *   "error": "Mensagem de erro detalhada"
     * }
     */
    public function index(): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Recuperar os produtos da loja logada
            $produtos = Produto::where('loja_id', $loja_id)->get();

            // Transforma os caminhos das imagens em URLs completas
            $produtos->transform(function ($produto) {
                if ($produto->foto_capa_path) {
                    $produto->foto_capa_path = url("storage/{$produto->foto_capa_path}");
                }
                if ($produto->imagens_paths) {
                    $produto->imagens_paths = array_map(function ($path) {
                        return url("storage/{$path}");
                    }, $produto->imagens_paths);
                }
                return $produto;
            });

            return response()->json([
                'success' => true,
                'data' => $produtos
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar os produtos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar um novo produto
     *
     * Cria um novo produto associado à loja do usuário autenticado.
     *
     * @authenticated
     * @url POST /api/produtos/create
     * @bodyParam nome string required Nome do produto. Exemplo: Produto Exemplo
     * @bodyParam descricao string required Descrição do produto. Exemplo: Descrição do produto
     * @bodyParam referencia string required Referência única do produto. Exemplo: REF123
     * @bodyParam codigo_unico_produto string Código único do produto. Exemplo: COD123
     * @bodyParam codigo_barras string Código de barras do produto. Exemplo: 7891234567890
     * @bodyParam preco_compra number required Preço de compra do produto. Exemplo: 50.00
     * @bodyParam preco_venda number required Preço de venda do produto. Exemplo: 100.00
     * @bodyParam iva number required Percentual de IVA (0-100). Exemplo: 17
     * @bodyParam gerir_stock string required Se gerencia estoque ("sim" ou "nao"). Exemplo: sim
     * @bodyParam preco_promocional number Preço promocional do produto. Exemplo: 80.00
     * @bodyParam categoria_id integer required ID da categoria. Exemplo: 1
     * @bodyParam marca_id integer required ID da marca. Exemplo: 1
     * @bodyParam fornecedor_id integer required ID do fornecedor. Exemplo: 1
     * @bodyParam peso number Peso do produto em kg. Exemplo: 1.5
     * @bodyParam largura number Largura do produto em cm. Exemplo: 10.0
     * @bodyParam altura number Altura do produto em cm. Exemplo: 5.0
     * @bodyParam comprimento number Comprimento do produto em cm. Exemplo: 15.0
     * @bodyParam foto_capa file Imagem de capa do produto (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam imagens array Lista de imagens adicionais do produto.
     * @bodyParam imagens.* file Imagem adicional (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam video_url string URL de um vídeo do produto. Exemplo: https://example.com/video
     * @bodyParam status string Status do produto ("ativo" ou "inativo"). Exemplo: ativo
     * @bodyParam destaque boolean Se o produto é destacado. Exemplo: true
     * @bodyParam novidade boolean Se o produto é novidade. Exemplo: false
     * @bodyParam produto_em_oferta boolean Se o produto está em oferta. Exemplo: false
     * @bodyParam frete_gratis boolean Se o produto tem frete grátis. Exemplo: true
     * @bodyParam prazo_envio integer Prazo de envio em dias. Exemplo: 3
     * @bodyParam variacoes boolean Se o produto possui variações. Exemplo: false
     * @bodyParam desconto_id integer nullable ID do desconto associado. Exemplo: 1
     * @bodyParam visualizacoes integer Número de visualizações. Exemplo: 100
     * @bodyParam avaliacao_media number Média de avaliações (0-5). Exemplo: 4.5
     * @bodyParam qtd_avaliacoes integer Quantidade de avaliações. Exemplo: 10
     * @response 201 {
     *   "id": 1,
     *   "nome": "Produto Exemplo",
     *   "descricao": "Descrição do produto",
     *   "referencia": "REF123",
     *   "codigo_unico_produto": "COD123",
     *   "codigo_barras": "7891234567890",
     *   "preco_compra": 50.00,
     *   "preco_venda": 100.00,
     *   "iva": 17,
     *   "gerir_stock": "sim",
     *   "preco_promocional": 80.00,
     *   "categoria_id": 1,
     *   "marca_id": 1,
     *   "fornecedor_id": 1,
     *   "loja_id": 1,
     *   "peso": 1.5,
     *   "largura": 10.0,
     *   "altura": 5.0,
     *   "comprimento": 15.0,
     *   "foto_capa": "exemplo.jpg",
     *   "foto_capa_path": "http://example.com/storage/assets/produtos/fotos/exemplo.jpg",
     *   "imagens": ["imagem1.jpg"],
     *   "imagens_paths": ["http://example.com/storage/assets/produtos/fotos/imagem1.jpg"],
     *   "video_url": "https://example.com/video",
     *   "status": "ativo",
     *   "destaque": true,
     *   "novidade": false,
     *   "produto_em_oferta": false,
     *   "frete_gratis": true,
     *   "prazo_envio": 3,
     *   "variacoes": false,
     *   "desconto_id": null,
     *   "visualizacoes": 100,
     *   "avaliacao_media": 4.5,
     *   "qtd_avaliacoes": 10,
     *   "created_at": "2025-05-28T15:54:00.000000Z",
     *   "updated_at": "2025-05-28T15:54:00.000000Z"
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 422 {
     *   "error": {
     *     "nome": ["O campo nome é obrigatório."],
     *     "referencia": ["A referência já está em uso."]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao criar produto",
     *   "message": "Mensagem de erro detalhada"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Validação dos campos
            $request->validate([
                'nome' => 'required|string|max:255',
                'descricao' => 'nullable|string',
                'referencia' => 'required|string|unique:produtos,referencia',
                'codigo_unico_produto' => 'required|string|unique:produtos,codigo_unico_produto',
                'codigo_barras' => 'nullable|string',
                'preco_compra' => 'required|numeric|min:0',
                'preco_venda' => 'required|numeric|min:0',
                'iva' => 'nullable|numeric|min:0|max:100',
                'gerir_stock' => 'required|in:sim,nao',
                'preco_promocional' => 'nullable|numeric|min:0',
                'categoria_id' => 'required|exists:categoria,id',
                'marca_id' => 'required|exists:marcas,id',
                'fornecedor_id' => 'required|exists:fornecedores,id',
                'peso' => 'nullable|numeric|min:0',
                'largura' => 'nullable|numeric|min:0',
                'altura' => 'nullable|numeric|min:0',
                'comprimento' => 'nullable|numeric|min:0',
                'foto_capa' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'imagens' => 'nullable|array',
                'imagens.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'video_url' => 'nullable|url',
                'status' => 'required|in:ativo,inativo',
                'destaque' => 'nullable|boolean',
                'novidade' => 'nullable|boolean',
                'produto_em_oferta' => 'nullable|boolean',
                'frete_gratis' => 'nullable|boolean',
                'prazo_envio' => 'nullable|integer|min:0',
                'variacoes' => 'nullable|boolean',
                'desconto_id' => 'nullable|exists:descontos,id',
                'visualizacoes' => 'nullable|integer|min:0',
                'avaliacao_media' => 'nullable|numeric|min:0|max:5',
                'qtd_avaliacoes' => 'nullable|integer|min:0',
            ]);

            // Upload da foto de capa
            $fotoCapaPath = $this->uploadArquivo($request, 'foto_capa', 'fotos');

            // Upload das imagens adicionais
            $imagensPaths = [];
            if ($request->hasFile('imagens')) {
                foreach ($request->file('imagens') as $index => $imagem) {
                    $path = $this->uploadArquivo($request, 'imagens', 'fotos', $index);
                    if ($path) {
                        $imagensPaths[] = $path;
                    }
                }
            }

            // Criando o produto
            $produto = Produto::create([
                'nome' => $request->nome,
                'descricao' => $request->descricao,
                'referencia' => $request->referencia,
                'codigo_unico_produto' => $request->codigo_unico_produto,
                'codigo_barras' => $request->codigo_barras,
                'preco_compra' => $request->preco_compra,
                'preco_venda' => $request->preco_venda,
                'iva' => $request->iva,
                'gerir_stock' => $request->gerir_stock,
                'preco_promocional' => $request->preco_promocional,
                'categoria_id' => $request->categoria_id,
                'marca_id' => $request->marca_id,
                'fornecedor_id' => $request->fornecedor_id,
                'loja_id' => $loja_id,
                'peso' => $request->peso,
                'largura' => $request->largura,
                'altura' => $request->altura,
                'comprimento' => $request->comprimento,
                'foto_capa' => $fotoCapaPath ? basename($fotoCapaPath) : null,
                'foto_capa_path' => $fotoCapaPath,
                'imagens' => !empty($imagensPaths) ? array_map('basename', $imagensPaths) : null,
                'imagens_paths' => !empty($imagensPaths) ? $imagensPaths : null,
                'video_url' => $request->video_url,
                'status' => $request->status,
                'destaque' => $request->destaque,
                'novidade' => $request->novidade,
                'produto_em_oferta' => $request->produto_em_oferta,
                'frete_gratis' => $request->frete_gratis,
                'prazo_envio' => $request->prazo_envio,
                'variacoes' => $request->variacoes,
                'desconto_id' => $request->desconto_id,
                'visualizacoes' => $request->visualizacoes ?? 0,
                'avaliacao_media' => $request->avaliacao_media ?? 0,
                'qtd_avaliacoes' => $request->qtd_avaliacoes ?? 0,
            ]);

            // Transforma os caminhos em URLs completas
            if ($produto->foto_capa_path) {
                $produto->foto_capa_path = url("storage/{$produto->foto_capa_path}");
            }
            if ($produto->imagens_paths) {
                $produto->imagens_paths = array_map(function ($path) {
                    return url("storage/{$path}");
                }, $produto->imagens_paths);
            }

            return response()->json($produto, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar produto', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Exibir um produto
     *
     * Retorna os detalhes de um produto específico com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id string required O ID do produto. Exemplo: 1
     * @response 200 {
     *   "id": 1,
     *   "nome": "Produto Exemplo",
     *   "descricao": "Descrição do produto",
     *   "referencia": "REF123",
     *   "codigo_unico_produto": "COD123",
     *   "codigo_barras": "7891234567890",
     *   "preco_compra": 50.00,
     *   "preco_venda": 100.00,
     *   "iva": 17,
     *   "gerir_stock": "sim",
     *   "preco_promocional": 80.00,
     *   "categoria_id": 1,
     *   "marca_id": 1,
     *   "fornecedor_id": 1,
     *   "loja_id": 1,
     *   "peso": 1.5,
     *   "largura": 10.0,
     *   "altura": 5.0,
     *   "comprimento": 15.0,
     *   "foto_capa_path": "http://example.com/storage/assets/produtos/fotos/exemplo.jpg",
     *   "imagens_paths": ["http://example.com/storage/assets/produtos/fotos/imagem1.jpg"],
     *   "video_url": "https://example.com/video",
     *   "status": "ativo",
     *   "destaque": true,
     *   "novidade": false,
     *   "produto_em_oferta": false,
     *   "frete_gratis": true,
     *   "prazo_envio": 3,
     *   "variacoes": false,
     *   "desconto_id": null,
     *   "visualizacoes": 100,
     *   "avaliacao_media": 4.5,
     *   "qtd_avaliacoes": 10,
     *   "created_at": "2025-05-28T15:54:00.000000Z",
     *   "updated_at": "2025-05-28T15:54:00.000000Z"
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Produto não encontrado"
     * }
     */
    public function show($id): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Busca o produto por loja_id e id
            $produto = Produto::where('loja_id', $loja_id)->findOrFail($id);

            // Transforma os caminhos em URLs completas
            if ($produto->foto_capa_path) {
                $produto->foto_capa_path = url("storage/{$produto->foto_capa_path}");
            }
            if ($produto->imagens_paths) {
                $produto->imagens_paths = array_map(function ($path) {
                    return url("storage/{$path}");
                }, $produto->imagens_paths);
            }

            return response()->json($produto, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Produto não encontrado'], 404);
        }
    }

    /**
     * Atualizar um produto
     *
     * Atualiza os dados de um produto existente com base no ID fornecido.
     *
     * @authenticated
     * @url PUT /api/produtos/update/{id}
     * @urlParam id integer required O ID do produto. Exemplo: 1
     * @bodyParam nome string Nome do produto. Exemplo: Produto Exemplo
     * @bodyParam descricao string Descrição do produto. Exemplo: Descrição do produto
     * @bodyParam referencia string Referência única do produto. Exemplo: REF123
     * @bodyParam codigo_unico_produto string Código único do produto. Exemplo: COD123
     * @bodyParam codigo_barras string Código de barras do produto. Exemplo: 7891234567890
     * @bodyParam preco_compra number Preço de compra do produto. Exemplo: 50.00
     * @bodyParam preco_venda number Preço de venda do produto. Exemplo: 100.00
     * @bodyParam iva number Percentual de IVA (0-100). Exemplo: 17
     * @bodyParam gerir_stock string Se gerencia estoque ("sim" ou "nao"). Exemplo: sim
     * @bodyParam preco_promocional number Preço promocional do produto. Exemplo: 80.00
     * @bodyParam categoria_id integer ID da categoria. Exemplo: 1
     * @bodyParam marca_id integer ID da marca. Exemplo: 1
     * @bodyParam fornecedor_id integer ID do fornecedor. Exemplo: 1
     * @bodyParam peso number Peso do produto em kg. Exemplo: 1.5
     * @bodyParam largura number Largura do produto em cm. Exemplo: 10.0
     * @bodyParam altura number Altura do produto em cm. Exemplo: 5.0
     * @bodyParam comprimento number Comprimento do produto em cm. Exemplo: 15.0
     * @bodyParam foto_capa file nullable Imagem de capa do produto (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam imagens[] file nullable Imagem adicional (jpg, jpeg, png, webp, máx. 2MB).
     * @bodyParam video_url string URL de um vídeo do produto. Exemplo: https://example.com/video
     * @bodyParam status string Status do produto ("ativo" ou "inativo"). Exemplo: ativo
     * @bodyParam destaque boolean Se o produto é destacado. Exemplo: true
     * @bodyParam novidade boolean Se o produto é novidade. Exemplo: false
     * @bodyParam produto_em_oferta boolean Se o produto está em oferta. Exemplo: false
     * @bodyParam frete_gratis boolean Se o produto tem frete grátis. Exemplo: true
     * @bodyParam prazo_envio integer Prazo de envio em dias. Exemplo: 3
     * @bodyParam variacoes boolean Se o produto possui variações. Exemplo: false
     * @bodyParam desconto_id integer nullable ID do desconto associado. Exemplo: 1
     * @bodyParam visualizacoes integer Número de visualizações. Exemplo: 100
     * @bodyParam avaliacao_media number Média de avaliações (0-5). Exemplo: 4.5
     * @bodyParam qtd_avaliacoes integer Quantidade de avaliações. Exemplo: 10
     * @response 200 {
     *   "id": 1,
     *   "nome": "Produto Exemplo",
     *   "descricao": "Descrição do produto",
     *   "referencia": "REF123",
     *   "codigo_unico_produto": "COD123",
     *   "codigo_barras": "7891234567890",
     *   "preco_compra": 50.00,
     *   "preco_venda": 100.00,
     *   "iva": 17,
     *   "gerir_stock": "sim",
     *   "preco_promocional": 80.00,
     *   "categoria_id": 1,
     *   "marca_id": 1,
     *   "fornecedor_id": 1,
     *   "loja_id": 1,
     *   "peso": 1.5,
     *   "largura": 10.0,
     *   "altura": 5.0,
     *   "comprimento": 15.0,
     *   "foto_capa": "exemplo.jpg",
     *   "foto_capa_path": "https://example.com/storage/assets/produtos/fotos/exemplo.jpg",
     *   "imagens": ["imagem1.jpg"],
     *   "imagens_paths": ["https://example.com/storage/assets/produtos/fotos/imagem1.jpg"],
     *   "video_url": "https://example.com/video",
     *   "status": "ativo",
     *   "destaque": true,
     *   "novidade": false,
     *   "produto_em_oferta": false,
     *   "frete_gratis": true,
     *   "prazo_envio": 3,
     *   "variacoes": false,
     *   "desconto_id": null,
     *   "visualizacoes": 100,
     *   "avaliacao_media": 4.5,
     *   "qtd_avaliacoes": 10,
     *   "created_at": "2025-05-28T15:54:00.000000Z",
     *   "updated_at": "2025-05-28T15:54:00.000000Z"
     * }
     * @responseError 403 {
     *   "error": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Produto não encontrado"
     * }
     * @responseError 422 {
     *   "error": {
     *     "nome": ["O campo nome é obrigatório."],
     *     "referencia": ["A referência já está em uso."]
     *   }
     * }
     * @responseError 500 {
     *   "error": "Erro ao atualizar produto",
     *   "message": "Mensagem de erro detalhada"
     * }
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Busca o produto por loja_id e id
            $produto = Produto::where('loja_id', $loja_id)->findOrFail($id);

            // Validação dos campos
            $request->validate([
                'nome' => 'sometimes|required|string|max:255',
                'descricao' => 'nullable|string',
                'referencia' => 'sometimes|required|string|unique:produtos,referencia,' . $id,
                'codigo_unico_produto' => 'sometimes|required|string|unique:produtos,codigo_unico_produto,' . $id,
                'codigo_barras' => 'nullable|string',
                'preco_compra' => 'sometimes|required|numeric|min:0',
                'preco_venda' => 'sometimes|required|numeric|min:0',
                'iva' => 'nullable|numeric|min:0|max:100',
                'gerir_stock' => 'sometimes|required|in:sim,nao',
                'preco_promocional' => 'nullable|numeric|min:0',
                'categoria_id' => 'sometimes|required|exists:categoria,id',
                'marca_id' => 'sometimes|required|exists:marcas,id',
                'fornecedor_id' => 'sometimes|required|exists:fornecedores,id',
                'loja_id' => 'sometimes|required|exists:lojas,id',
                'peso' => 'nullable|numeric|min:0',
                'largura' => 'nullable|numeric|min:0',
                'altura' => 'nullable|numeric|min:0',
                'comprimento' => 'nullable|numeric|min:0',
                'foto_capa' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'imagens' => 'nullable|array',
                'imagens.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
                'video_url' => 'nullable|url',
                'status' => 'sometimes|required|in:ativo,inativo',
                'destaque' => 'nullable|boolean',
                'novidade' => 'nullable|boolean',
                'produto_em_oferta' => 'nullable|boolean',
                'frete_gratis' => 'nullable|boolean',
                'prazo_envio' => 'nullable|integer|min:0',
                'variacoes' => 'nullable|boolean',
                'desconto_id' => 'nullable|exists:descontos,id',
                'visualizacoes' => 'nullable|integer|min:0',
                'avaliacao_media' => 'nullable|numeric|min:0|max:5',
                'qtd_avaliacoes' => 'nullable|integer|min:0',
            ]);

            // Coleta os dados enviados
            $dados = $request->only([
                'nome', 'descricao', 'referencia', 'codigo_unico_produto', 'codigo_barras',
                'preco_compra', 'preco_venda', 'iva', 'gerir_stock', 'preco_promocional',
                'categoria_id', 'marca_id', 'fornecedor_id', 'loja_id', 'peso', 'largura',
                'altura', 'comprimento', 'video_url', 'status', 'destaque', 'novidade',
                'produto_em_oferta', 'frete_gratis', 'prazo_envio', 'variacoes',
                'desconto_id', 'visualizacoes', 'avaliacao_media', 'qtd_avaliacoes'
            ]);

            // Atualiza a foto de capa, se enviada
            if ($request->hasFile('foto_capa')) {
                if ($produto->foto_capa_path) {
                    Storage::disk('public')->delete($produto->foto_capa_path);
                }
                $fotoCapaPath = $this->uploadArquivo($request, 'foto_capa', 'fotos');
                $dados['foto_capa'] = $fotoCapaPath ? basename($fotoCapaPath) : null;
                $dados['foto_capa_path'] = $fotoCapaPath;
            }

            // Atualiza as imagens adicionais, se enviadas
            if ($request->hasFile('imagens')) {
                if ($produto->imagens_paths) {
                    foreach ($produto->imagens_paths as $path) {
                        Storage::disk('public')->delete($path);
                    }
                }
                $imagensPaths = [];
                foreach ($request->file('imagens') as $index => $imagem) {
                    $path = $this->uploadArquivo($request, 'imagens', 'fotos', $index);
                    if ($path) {
                        $imagensPaths[] = $path;
                    }
                }
                $dados['imagens'] = !empty($imagensPaths) ? array_map('basename', $imagensPaths) : null;
                $dados['imagens_paths'] = !empty($imagensPaths) ? $imagensPaths : null;
            }

            // Atualiza o produto
            $produto->update($dados);

            // Transforma os caminhos em URLs completas
            if ($produto->foto_capa_path) {
                $produto->foto_capa_path = url("storage/{$produto->foto_capa_path}");
            }
            if ($produto->imagens_paths) {
                $produto->imagens_paths = array_map(function ($path) {
                    return url("storage/{$path}");
                }, $produto->imagens_paths);
            }

            return response()->json($produto, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar produto', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remover um produto
     *
     * Remove um produto com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id string required O ID do produto. Exemplo: 1
     * @response 200 {
     *   "message": "Produto removido com sucesso"
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Produto não encontrado"
     * }
     * @responseError 500 {
     *   "error": "Erro ao remover produto"
     * }
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Busca o produto por loja_id e id
            $produto = Produto::where('loja_id', $loja_id)->findOrFail($id);

            // Deleta as imagens do disco, se existirem
            if ($produto->foto_capa_path) {
                Storage::disk('public')->delete($produto->foto_capa_path);
            }
            if ($produto->imagens_paths) {
                foreach ($produto->imagens_paths as $path) {
                    Storage::disk('public')->delete($path);
                }
            }

            // Deleta o produto
            $produto->delete();

            return response()->json(['message' => 'Produto removido com sucesso'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao remover produto'], 500);
        }
    }

    /**
     * Exportar produtos para PDF
     *
     * Gera um arquivo PDF contendo todos os produtos e retorna a URL do arquivo gerado.
     *
     * @response 200 {
     *   "message": "Arquivo PDF gerado com sucesso",
     *   "file_name": "produtos_2025-05-28_15-54-00.pdf",
     *   "file_path": "exports/pdf/produtos_2025-05-28_15-54-00.pdf",
     *   "url": "http://example.com/storage/exports/pdf/produtos_2025-05-28_15-54-00.pdf"
     * }
     * @responseError 404 {
     *   "message": "Nenhum produto encontrado"
     * }
     */
    public function exportProdutosPdf(): JsonResponse
    {
        $produtos = Produto::all();

        if ($produtos->isEmpty()) {
            return response()->json(['message' => 'Nenhum produto encontrado'], 404);
        }

        $path = storage_path('app/private/exports/pdf');

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $dataAtual = Carbon::now()->format('Y-m-d_H-i-s');
        $fileName = "produtos_{$dataAtual}.pdf";
        $filePath = "exports/pdf/{$fileName}";

        $pdf = Pdf::loadView('produtos', compact('produtos'));
        Storage::put($filePath, $pdf->output());

        return response()->json([
            'message' => 'Arquivo PDF gerado com sucesso',
            'file_name' => $fileName,
            'file_path' => $filePath,
            'url' => asset("storage/{$filePath}")
        ], 200);
    }

    /**
     * Exportar produtos para CSV
     *
     * Gera um arquivo CSV contendo todos os produtos e retorna a URL do arquivo gerado.
     *
     * @response 200 {
     *   "message": "Arquivo CSV gerado com sucesso",
     *   "file_name": "produtos_2025-05-28_15-54-00.csv",
     *   "file_path": "exports/csv/produtos_2025-05-28_15-54-00.csv",
     *   "url": "http://example.com/storage/exports/csv/produtos_2025-05-28_15-54-00.csv"
     * }
     * @responseError 404 {
     *   "message": "Nenhum produto encontrado"
     * }
     */
    public function exportProdutosCsv(): JsonResponse
    {
        $produtos = Produto::all();

        if ($produtos->isEmpty()) {
            return response()->json(['message' => 'Nenhum produto encontrado'], 404);
        }

        $path = storage_path('app/private/exports/csv');

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $dataAtual = Carbon::now()->format('Y-m-d_H-i-s');
        $fileName = "produtos_{$dataAtual}.csv";
        $filePath = "exports/csv/{$fileName}";

        $handle = fopen(storage_path("app/private/exports/csv/{$fileName}"), 'w');

        // Definir cabeçalho do CSV
        fputcsv($handle, [
            'nome', 'descricao', 'referencia', 'codigo_unico_produto', 'codigo_barras',
            'preco_compra', 'preco_venda', 'iva', 'gerir_stock', 'preco_promocional',
            'categoria_id', 'marca_id', 'fornecedor_id', 'loja_id', 'peso', 'largura',
            'altura', 'comprimento', 'foto_capa', 'imagens', 'video_url', 'status',
            'destaque', 'novidade', 'produto_em_oferta', 'frete_gratis', 'prazo_envio',
            'variacoes', 'desconto_id', 'visualizacoes', 'avaliacao_media', 'qtd_avaliacoes'
        ]);

        // Adicionar os produtos ao CSV
        foreach ($produtos as $produto) {
            fputcsv($handle, [
                $produto->nome,
                $produto->descricao,
                $produto->referencia,
                $produto->codigo_unico_produto,
                $produto->codigo_barras,
                $produto->preco_compra,
                $produto->preco_venda,
                $produto->iva,
                $produto->gerir_stock,
                $produto->preco_promocional,
                $produto->categoria_id,
                $produto->marca_id,
                $produto->fornecedor_id,
                $produto->loja_id,
                $produto->peso,
                $produto->largura,
                $produto->altura,
                $produto->comprimento,
                $produto->foto_capa,
                json_encode($produto->imagens),
                $produto->video_url,
                $produto->status,
                $produto->destaque,
                $produto->novidade,
                $produto->produto_em_oferta,
                $produto->frete_gratis,
                $produto->prazo_envio,
                $produto->variacoes,
                $produto->desconto_id,
                $produto->visualizacoes,
                $produto->avaliacao_media,
                $produto->qtd_avaliacoes
            ]);
        }

        fclose($handle);

        return response()->json([
            'message' => 'Arquivo CSV gerado com sucesso',
            'file_name' => $fileName,
            'file_path' => $filePath,
            'url' => asset("storage/{$filePath}")
        ], 200);
    }

    /**
     * Atualizar estoque de um produto
     *
     * Atualiza o valor do campo estoque de um produto com base no ID fornecido.
     *
     * @authenticated
     * @urlParam id string required O ID do produto. Exemplo: 1
     * @bodyParam estoque integer required Quantidade de estoque. Exemplo: 100
     * @response 200 {
     *   "message": "Estoque atualizado com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "nome": "Produto Exemplo",
     *     "estoque": 100,
     *     "created_at": "2025-05-28T15:54:00.000000Z",
     *     "updated_at": "2025-05-28T15:54:00.000000Z"
     *   }
     * }
     * @responseError 403 {
     *   "success": false,
     *   "message": "Usuário não possui loja associada"
     * }
     * @responseError 404 {
     *   "error": "Produto não encontrado."
     * }
     * @responseError 422 {
     *   "error": {
     *     "estoque": ["O campo estoque é obrigatório."]
     *   }
     * }
     */
    public function updateStock(Request $request, $id): JsonResponse
    {
        try {
            // Valida se o usuário possui loja associada
            if ($errorResponse = $this->validateLojaId()) {
                return $errorResponse;
            }
            $loja_id = $this->getLojaId();

            // Validação do campo de estoque
            $request->validate([
                'estoque' => 'required|integer|min:0',
            ]);

            // Buscar o produto pelo ID e loja_id
            $produto = Produto::where('loja_id', $loja_id)->find($id);

            // Se não existir, retorna erro 404
            if (!$produto) {
                return response()->json(['error' => 'Produto não encontrado.'], 404);
            }

            // Atualizar apenas o campo de estoque
            $produto->estoque = $request->estoque;
            $produto->save();

            // Retornar o produto já atualizado
            return response()->json([
                'message' => 'Estoque atualizado com sucesso.',
                'data' => $produto
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar estoque', 'message' => $e->getMessage()], 500);
        }
    }
}
