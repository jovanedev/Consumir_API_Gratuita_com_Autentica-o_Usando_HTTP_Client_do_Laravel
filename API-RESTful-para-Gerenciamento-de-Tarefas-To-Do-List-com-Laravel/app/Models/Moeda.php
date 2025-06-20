<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Moeda extends Model
{
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'moedas';

    protected $fillable = [
        'loja_id', 
        'nome', 
        'codigo', 
        'simbolo', 
        'taxa_cambio', 
        'padrao', 
        'status'
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

}
