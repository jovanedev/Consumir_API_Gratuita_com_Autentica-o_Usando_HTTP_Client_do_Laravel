<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'produtos';

    protected $fillable = [
        'nome',
        'descricao',
        'referencia',
        'codigo_unico_produto',
        'codigo_barras',
        'preco_compra',
        'preco_venda',
        'iva',
        'gerir_stock',
        'estoque',
        'preco_promocional',
        'categoria_id',
        'marca_id',
        'fornecedor_id',
        'loja_id',
        'peso',
        'largura',
        'altura',
        'comprimento',
        'foto_capa',
        'imagens',
        'video_url',
        'status',
        'destaque',
        'novidade',
        'produto_em_oferta',
        'frete_gratis',
        'prazo_envio',
        'variacoes',
        'desconto_id',
        'visualizacoes',
        'avaliacao_media',
        'qtd_avaliacoes',
    ];

    // Adicionando a definição de cast para garantir que as imagens sejam tratadas corretamente
    protected $casts = [
        // Garantindo que o campo imagens seja tratado como um array
        'imagens' => 'array',
    ];

    public function variacoes() {
        return $this->hasMany(ProdutoVariacao::class);
    }
    
    public function loja()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }
    

}
