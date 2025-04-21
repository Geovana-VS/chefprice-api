<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('receita_imagens', function (Blueprint $table) {
            $table->foreignId('id_receita')->constrained('receitas')->onDelete('cascade');
            $table->foreignId('id_imagem')->constrained('imagens')->onDelete('cascade');
            $table->primary(['id_receita', 'id_imagem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receita_imagens');
    }
};