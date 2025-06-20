<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdutosEmOferta extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'template_id',
        'titulo',
        'tipo_visualizacao',
        'produtos_por_linha_celulares',
        'produtos_por_linha_computadores',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }
}
