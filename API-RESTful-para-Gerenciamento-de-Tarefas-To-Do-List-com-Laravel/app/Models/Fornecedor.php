<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{
    
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'fornecedores';

    protected $fillable = [
        'loja_id',
        'nome',
        'email',
        'telefone',
        'endereco',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

}
