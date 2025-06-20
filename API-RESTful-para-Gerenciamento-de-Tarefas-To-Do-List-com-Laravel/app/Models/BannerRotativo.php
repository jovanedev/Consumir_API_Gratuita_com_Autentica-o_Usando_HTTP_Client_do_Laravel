<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerRotativo extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'template_id',
        'imagem_desktop',
        'imagem_desktop_path',
        'imagem_mobile',
        'imagem_mobile_path',
        'largura_tela',
        'efeito_movimento',
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
