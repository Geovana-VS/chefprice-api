<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtapaReceita extends Model
{
    use HasFactory;

    protected $table = 'etapa_receitas';
    public $timestamps = false;

    protected $fillable = [
        'id_receita',
        'numero_etapa',
        'instrucoes',
    ];

    public function receita(): BelongsTo
    {
        return $this->belongsTo(Receita::class, 'id_receita');
    }
}