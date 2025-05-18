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
        'quantidade',
        'unidade_medida',
        'descricao',
    ];

    protected $casts = [
        'id_categoria' => 'integer',
        'quantidade' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    public function historico(): HasMany
    {
        return $this->hasMany(ProdutoHistorico::class, 'id_produto');
    }

        public function listasCompra(): BelongsToMany
    {
        return $this->belongsToMany(ListaCompra::class, 'lista_compra_produtos', 'id_produto', 'id_lista_compra')
                    ->withPivot(['quantidade', 'unidade_medida', 'observacao', 'comprado', 'created_at', 'updated_at'])
                    ->withTimestamps();
    }
}