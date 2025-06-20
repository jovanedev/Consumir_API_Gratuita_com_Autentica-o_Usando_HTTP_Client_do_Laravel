<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MensagemInstitucional extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'template_id',
        'subtitulo',
        'titulo',
        'titulo_italico',
        'link',
        'botao',
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
