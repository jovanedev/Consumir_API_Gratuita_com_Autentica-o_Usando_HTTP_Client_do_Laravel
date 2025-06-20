<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PopupPromocional extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'template_id',
        'mostrar_popup',
        'imagem_path',
        'titulo',
        'descricao',
        'texto_botao',
        'link_botao',
        'permitir_inscricao_newsletter',
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
