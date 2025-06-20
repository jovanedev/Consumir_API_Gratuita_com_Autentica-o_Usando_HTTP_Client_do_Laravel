<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MostrarProduto extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'template_id',
        'mostrar_calculadora_frete',
        'mostrar_parcelas',
        'mostrar_preco_desconto',
        'variacoes_como_botoes',
        'variacoes_cor_como_foto',
        'link_guia_medidas',
        'mostrar_estoque',
        'mostrar_mensagem_ultima_unidade',
        'mensagem_ultima_unidade',
        'descricao_largura_total',
        'permitir_comentarios_facebook',
        'facebook_perfil_id',
        'titulo_produtos_alternativos',
        'titulo_produtos_complementares',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }
}
