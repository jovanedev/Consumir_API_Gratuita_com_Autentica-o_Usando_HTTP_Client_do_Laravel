<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'emails';

    protected $fillable = [
        'loja_id', 
        'descricao', 
        'conteudo', 
        'conteudo_html', 
        'status_conteudo_html', 
        'tipo'
    ];

    protected $casts = [
        'conteudo' => 'array',
        'conteudo_html' => 'array'
    ];
}
