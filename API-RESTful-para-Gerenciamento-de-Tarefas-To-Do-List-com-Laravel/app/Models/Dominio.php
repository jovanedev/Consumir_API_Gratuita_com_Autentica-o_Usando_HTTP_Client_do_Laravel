<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dominio extends Model
{
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'dominios';

    protected $fillable = [
        'loja_id', 
        'dominio', 
        'principal', 
        'status_dominio', 
        'status_ssl'
    ];
}
