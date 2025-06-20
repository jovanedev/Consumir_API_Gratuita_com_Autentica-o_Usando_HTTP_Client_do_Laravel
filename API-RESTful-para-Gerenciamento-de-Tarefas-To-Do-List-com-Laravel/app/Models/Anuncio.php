<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anuncio extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'template_id',
        'titulo',
        'texto',
        'link',
        'imagem_desktop',
        'imagem_desktop_path',
        'imagem_mobile',
        'imagem_mobile_path',
        'carregar_imagens_mobile',
    ];

    public function loja()
    {
        return $this->belongsto(Loja::class, 'loja_id');
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }
}
