<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cabecalhos_gt extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'template_id',
        'cor_fundo',
        'cor_texto_icones',
        'tamanho_logo',
        'mostrar_idiomas',
        'cabecalho_em_celulares',
        'cabecalho_em_computadores',
        'barra_anuncio',
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
