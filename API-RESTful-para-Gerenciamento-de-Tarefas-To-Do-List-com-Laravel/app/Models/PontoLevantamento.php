<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PontoLevantamento extends Model
{
    
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'ponto_levantamentos';

    protected $fillable = 
    [
        'loja_id',
        'nome_local', 
        'estado', 
        'cidade', 
        'bairro', 
        'rua', 
        'numero', 
        'complemento'
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

}
