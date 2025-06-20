<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannersCategoriasGt extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'template_id',
        'categoria_id',
        'titulo',
        'mostrar_texto_fora_imagem',
        'mostrar_banners_carrossel',
        'mesma_altura_banners',
        'remover_espacos_banners',
        'banners_por_linha',
        'imagem_path_computador',
        'carregar_imagens_celular',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
