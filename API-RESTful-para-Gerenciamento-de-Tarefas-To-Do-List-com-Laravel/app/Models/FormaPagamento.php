<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormaPagamento extends Model
{
    
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'forma_pagamentos';

    protected $fillable = 
    [
        'loja_id', 
        'meio_pagamentos_id', 
        'dados_conta'
    ];

    public function meioPagamento()
    {
        return $this->belongsTo(MeioPagamento::class, 'meio_pagamentos_id');
    }

    public function loja()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

}
