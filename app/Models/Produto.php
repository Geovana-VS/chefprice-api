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
        'preco_padrao',
    ];

    protected $casts = [
        'id_categoria' => 'integer',
        'quantidade' => 'decimal:3',
        'preco_padrao' => 'decimal:2',
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

    public function nasListasDeCompra(): HasMany
    {
        return $this->hasMany(ListaCompraProduto::class, 'id_produto');
    }
}
