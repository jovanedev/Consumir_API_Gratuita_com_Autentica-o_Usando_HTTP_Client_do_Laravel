<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loja extends Model
{

    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'lojas';

    protected $fillable = [
        'nome',
        'pasta',
        'descricao',
        'email',
        'telefone',
        'endereco',
        'logomarca',
        'categoria',
        'url_loja',
        'cor_principal',
        'cores_auxiliares',
        'facebook',
        'instagram',
    ];


}
