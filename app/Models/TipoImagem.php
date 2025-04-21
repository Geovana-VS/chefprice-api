<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoImagem extends Model
{
    use HasFactory;

    protected $table = 'tipo_imagens';

    public $timestamps = false;

    protected $fillable = [
        'nome',
    ];

    public function imagens(): HasMany
    {
        return $this->hasMany(Imagem::class, 'id_tipo_imagem');
    }
}