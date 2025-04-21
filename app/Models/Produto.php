<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Produto extends Model
{
    use HasFactory;


    protected $table = 'produtos';
    public $timestamps = true;

    protected $fillable = [
        'codigo_barra',
        'nome',
        'id_categoria',
        'unidade_medida',
        'descricao',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }
    public function imagens(): BelongsToMany
    {
        return $this->belongsToMany(Imagem::class, 'produto_imagens', 'id_produto', 'id_imagem');
    }

    public function ingredienteEmReceitas(): HasMany
    {
        return $this->hasMany(IngredienteReceita::class, 'id_produto');
    }

    // Relationship: Product has many History records
    public function historico(): HasMany
    {
        return $this->hasMany(ProdutoHistorico::class, 'id_produto');
    }
}