<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'checkouts';

    protected $fillable = 
    [
        'loja_id',
        'cores_layout', 
        'pedir_telefone', 
        'pedir_endereco', 
        'mensagem_cliente',
        'mensagem_segmento', 
        'compra', 
        'checkout_acelerado'
    ];

}
