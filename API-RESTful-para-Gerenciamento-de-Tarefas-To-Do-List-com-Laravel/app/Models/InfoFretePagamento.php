<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfoFretePagamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'template_id',
        'usar_cores_secao',
        'cor_fundo',
        'cor_texto',
        'mostrar_banners_home',
        'imagem_path',
        'icone',
        'titulo',
        'descricao',
        'link',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class, 'info_frete_pagamento_id');
    }
}
