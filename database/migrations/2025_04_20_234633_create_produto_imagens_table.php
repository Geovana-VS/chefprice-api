<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::create('produto_imagens', function (Blueprint $table) {
            $table->foreignId('id_produto')->constrained('produtos')->onDelete('cascade');
            $table->foreignId('id_imagem')->constrained('imagens')->onDelete('cascade');
            $table->primary(['id_produto', 'id_imagem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_imagens');
    }
};