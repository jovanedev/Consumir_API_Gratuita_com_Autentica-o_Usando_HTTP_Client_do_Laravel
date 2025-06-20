<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nome',
        'data_nascimento',
        'genero',
        'documento_tipo',
        'documento_numero',
        'endereco_id',
        'status',
        'criado_em',
        'atualizado_em',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function endereco()
    {
        return $this->belongsTo(Endereco::class, 'endereco_id');
    }
}
