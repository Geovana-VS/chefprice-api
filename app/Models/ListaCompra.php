<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListaCompra extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'listas_compras';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_usuario',
        'id_lista_compra_status',
        'nome_lista',
        'descricao',
        'data_conclusao',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id_usuario' => 'integer',
        'id_lista_compra_status' => 'integer',
        'data_conclusao' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime', 
    ];

    /**
     * Get the user that owns the shopping list.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * Get the status of the shopping list.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ListaCompraStatus::class, 'id_lista_compra_status');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ListaCompraProduto::class, 'id_lista_compra');
    }
}
