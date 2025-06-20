<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Idioma extends Model
{
    
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'idiomas';

    protected $fillable = 
    [
        'loja_id',
        'codigo_idioma'
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

}
