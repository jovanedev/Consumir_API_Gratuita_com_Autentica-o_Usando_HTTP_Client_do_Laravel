<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'templates';

    protected $fillable = 
    [
        'nome_template', 
        'descricao', 
        'capa'
    ];

}
