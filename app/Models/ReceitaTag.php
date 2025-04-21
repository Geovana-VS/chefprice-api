<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReceitaTag extends Model
{
    use HasFactory;

    protected $table = 'receita_tags';
    public $timestamps = false;

    protected $fillable = [
        'nome',
    ];

    public function receitas(): BelongsToMany
    {
        return $this->belongsToMany(Receita::class, 'receita_tag_associacao', 'id_tag', 'id_receita');
    }
}