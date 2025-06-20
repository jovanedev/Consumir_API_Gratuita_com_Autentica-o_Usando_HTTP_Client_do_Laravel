<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'marcas';

    protected $fillable = [
        'loja_id',
        'nome',
        'descricao',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }
}
