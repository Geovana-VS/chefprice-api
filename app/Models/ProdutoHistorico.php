<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoHistorico extends Model
{
    use HasFactory;

    protected $table = 'produto_historicos';
    public $timestamps = true;

    protected $fillable = [
        'id_usuario',
        'id_produto',
        'preco_unitario',
        'quantidade',
        'preco_total',
        'data_compra',
        'desconto',
    ];

    protected $casts = [
        'preco_unitario' => 'decimal:2',
        'quantidade' => 'decimal:3',
        'preco_total' => 'decimal:2',
        'desconto' => 'decimal:2',
        'data_compra' => 'date',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'id_produto');
    }
}