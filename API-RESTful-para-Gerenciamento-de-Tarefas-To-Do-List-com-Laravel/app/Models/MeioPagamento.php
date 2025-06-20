<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeioPagamento extends Model
{
    
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'meio_pagamentos';

    protected $fillable = 
    [
        'nome', 
        'logo'
    ];

    public function formasPagamento()
    {
        return $this->hasMany(FormaPagamento::class, 'meio_pagamentos_id');
    }

}
