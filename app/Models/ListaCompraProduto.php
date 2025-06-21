<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListaCompraProduto extends Model
{
    use HasFactory;

    protected $table = 'lista_compra_produtos';
    public $timestamps = true;

    protected $fillable = [
        'id_lista_compra',
        'id_produto',
        'quantidade',
        'unidade_medida',
        'observacao',
        'comprado',
    ];

    protected $casts = [
        'quantidade' => 'decimal:3',
        'comprado' => 'boolean',
    ];

    /**
     * Define o relacionamento: este item pertence a uma Lista de Compra.
     */
    public function listaCompra(): BelongsTo
    {
        return $this->belongsTo(ListaCompra::class, 'id_lista_compra');
    }

    /**
     * Define o relacionamento: este item refere-se a um Produto.
     */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'id_produto');
    }
}