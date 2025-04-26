<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class Imagem extends Model
{
    use HasFactory;

    protected $table = 'imagens';
    public $timestamps = true;

    protected $fillable = [
        'id_usuario',
        'id_tipo_imagem',
        'nome_arquivo',
        'mime_type',
        'is_publico',
        'url',
        'caminho_storage',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'id_tipo_imagem' => 'integer',
        'is_publico' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['display_url'];


    public function getDisplayUrlAttribute(): ?string
    {
        $url = $this->attributes['url'] ?? null;
        $caminhoStorage = $this->attributes['caminho_storage'] ?? null;

        if ($url && Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        if ($caminhoStorage) {
            if (Storage::disk('public')->exists($caminhoStorage)) {
                return Storage::disk('public')->url($caminhoStorage);
            } else {
                Log::warning("Imagem record ID {$this->id} has caminho_storage '{$caminhoStorage}' but file does not exist in public disk.");
            }
        }

        // Return null if neither a valid URL nor an existing local path is found
        return null;
    }
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
