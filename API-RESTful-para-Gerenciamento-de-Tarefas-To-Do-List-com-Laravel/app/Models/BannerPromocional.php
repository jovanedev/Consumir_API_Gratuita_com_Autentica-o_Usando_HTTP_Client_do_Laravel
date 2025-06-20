<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerPromocional extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'template_id',
        'titulo',
        'texto_fora_imagem',
        'banners_carrossel',
        'mesma_altura',
        'remover_espacos',
        'banners_por_linha_desktop',
        'imagem_desktop_path',
        'imagem_mobile_path',
        'carregar_imagens_mobile',
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
