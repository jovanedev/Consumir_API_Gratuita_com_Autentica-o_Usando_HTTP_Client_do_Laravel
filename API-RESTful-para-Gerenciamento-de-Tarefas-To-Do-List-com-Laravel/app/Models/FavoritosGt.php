<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoritosGt extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'template_id',
        'favoritado',
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
