<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Redirecionamento extends Model
{

    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'redirecionamentos';

    protected $fillable =
    [
        'loja_id',
        'url_nova',
        'url_antiga'
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }

}
