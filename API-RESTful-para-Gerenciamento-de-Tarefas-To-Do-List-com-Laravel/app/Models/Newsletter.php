<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    use HasFactory;

    protected $fillable = [
        'aumentar_largura_tela',
        'usar_cores_newsletter',
        'cor_fundo',
        'cor_texto',
        'imagem_path',
        'titulo',
        'descricao',
    ];
}
