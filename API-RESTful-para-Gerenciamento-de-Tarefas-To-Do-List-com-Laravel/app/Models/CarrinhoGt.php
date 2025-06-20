<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarrinhoGt extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'template_id',
        'mostrar_botao_ver_mais',
        'valor_minimo_compra',
        'carrinho_rapido',
        'sugerir_produtos_complementares',
        'mostrar_calculadora_frete',
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
