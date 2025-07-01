<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ColecaoReceita extends Model
{
    use HasFactory;

    protected $table = 'colecao_receitas';

    public $timestamps = true;

    protected $fillable = [
        'id_usuario',
        'nome',
        'descricao',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the recipe collection.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * The recipes that belong to the collection.
     */
    public function receitas(): BelongsToMany
    {
        return $this->belongsToMany(Receita::class, 'colecao_receita_receita', 'id_colecao_receita', 'id_receita')
                    ->withTimestamps(); // Opcional, para rastrear quando uma receita foi adicionada/atualizada na coleção
    }
}