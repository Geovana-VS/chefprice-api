<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredienteReceita extends Model
{
    use HasFactory;

    protected $table = 'ingrediente_receitas';
    public $timestamps = false;

    protected $fillable = [
        'id_receita',
        'id_produto',
        'quantidade',
        'unidade',
        'observacoes',
    ];

    protected $casts = [
        'quantidade' => 'decimal:3',
    ];

    // Relationship: Ingredient line belongs to a Recipe
    public function receita(): BelongsTo
    {
        return $this->belongsTo(Receita::class, 'id_receita');
    }

    // Relationship: Ingredient line refers to a Product
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'id_produto');
    }
}