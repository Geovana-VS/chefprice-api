<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Imagem extends Model
{
    use HasFactory;

    protected $table = 'imagens';
    public $timestamps = true;

    protected $fillable = [
        'id_usuario',
        'id_tipo_imagem',
        'dados_imagem',
        'nome_arquivo',
        'nome_arquivo_storage',
        'mime_type',
        'is_publico',
    ];

    protected $casts = [
        'is_publico' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function tipoImagem(): BelongsTo
    {
        return $this->belongsTo(TipoImagem::class, 'id_tipo_imagem');
    }

    public function produtos(): BelongsToMany
    {
        return $this->belongsToMany(Produto::class, 'produto_imagens', 'id_imagem', 'id_produto');
    }
     public function receitas(): BelongsToMany
     {
         // Pivot table: 'receita_imagens', FKs: 'id_imagem', 'id_receita'
         return $this->belongsToMany(Receita::class, 'receita_imagens', 'id_imagem', 'id_receita');
     }
}