<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Receita extends Model
{
    use HasFactory;

    protected $table = 'receitas';
    public $timestamps = true;

    protected $fillable = [
        'id_usuario',
        'titulo',
        'descricao',
        'rendimento',
        'tempo_preparo',
    ];

    // Relationship: Recipe belongs to a User (Creator)
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    // Relationship: Recipe has many Ingredients (via IngredienteReceita)
    public function ingredientes(): HasMany
    {
        return $this->hasMany(IngredienteReceita::class, 'id_receita');
    }

     // Relationship: Recipe has many Steps
    public function etapas(): HasMany
    {
        // Order by step number
        return $this->hasMany(EtapaReceita::class, 'id_receita')->orderBy('numero_etapa');
    }

    // Relationship: Recipe has many Tags (Many-to-Many)
    public function tags(): BelongsToMany
    {
        // Pivot table: 'Receita_Tag_Associacao', FKs: 'id_receita', 'id_tag'
        return $this->belongsToMany(ReceitaTag::class, 'receita_tag_associacao', 'id_receita', 'id_tag');
    }

     // Relationship: Recipe has many Images (Many-to-Many via Receita_Imagem)
     public function imagens(): BelongsToMany
     {
         // Pivot table: 'receita_imagens', FKs: 'id_receita', 'id_imagem'
         return $this->belongsToMany(Imagem::class, 'receita_imagens', 'id_receita', 'id_imagem');
     }
}