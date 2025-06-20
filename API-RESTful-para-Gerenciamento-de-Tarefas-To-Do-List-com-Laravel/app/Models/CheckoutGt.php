<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckoutGt extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'pedido_id',
        'exibir_opcoes_entrega',
        'exibir_opcoes_pagamento',
        'exibir_resumo_pedido',
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
