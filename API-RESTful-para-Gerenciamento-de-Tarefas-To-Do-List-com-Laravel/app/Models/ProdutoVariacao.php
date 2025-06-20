<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdutoVariacao extends Model
{
    use HasFactory;
    
    // Defina explicitamente o nome da tabela
    protected $table = 'produto_variacoes';

    protected $fillable = [
        'produto_id',
        'tipo_variacao',
        'valor_variacao',
        'estoque',
        'preco_adicional'
    ];

    public function produto() {
        return $this->belongsTo(Produto::class);
    }
}
