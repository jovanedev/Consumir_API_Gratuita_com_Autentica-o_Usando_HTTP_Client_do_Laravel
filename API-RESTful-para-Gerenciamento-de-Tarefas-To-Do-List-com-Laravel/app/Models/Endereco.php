<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Endereco extends Model
{
    
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'enderecos';

    protected $fillable = 
    [
        'usuario_id', 
        'estado', 
        'cidade', 
        'bairro', 
        'rua', 
        'numero', 
        'complemento'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

}
