<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'pedidos';

    protected $fillable = [
        'cliente_id', 
        'loja_id', 
        'codigo_unico_pedido', 
        'status', 
        'valor_total',
        'valor_desconto', 
        'frete', 
        'tipo_frete', 
        'prazo_entrega',
        'endereco_entrega_id', 
        'metodo_pagamento', 
        'observacoes'
    ];

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function loja()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

    public function enderecoEntrega()
    {
        return $this->belongsTo(Endereco::class, 'endereco_entrega_id');
    }

    public function metodoPagamento()
    {
        return $this->belongsTo(FormaPagamento::class, 'metodo_pagamento');
    }

}
