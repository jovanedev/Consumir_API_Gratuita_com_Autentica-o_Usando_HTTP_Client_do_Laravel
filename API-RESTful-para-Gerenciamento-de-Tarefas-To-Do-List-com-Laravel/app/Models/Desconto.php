<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Desconto extends Model
{
    
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'descontos';

    protected $fillable = [
        'loja_id',
        'codigo',
        'tipo',
        'valor',
        'data_inicio',
        'data_fim',
        'status',
    ];

}
