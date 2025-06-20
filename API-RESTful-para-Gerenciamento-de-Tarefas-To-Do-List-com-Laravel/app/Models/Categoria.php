<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'categoria';

    protected $fillable = [
        'loja_id',
        'nome',
        'descricao',
        'slug',
        'loja_id',
        'status',
    ];

}
