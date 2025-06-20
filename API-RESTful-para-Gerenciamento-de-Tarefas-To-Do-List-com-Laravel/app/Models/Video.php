<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'template_id',
        'aumentar_largura_tela',
        'tipo_reproducao',
        'link_youtube',
        'imagem_path',
        'titulo',
        'descricao',
        'texto_botao',
        'link_botao',
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
