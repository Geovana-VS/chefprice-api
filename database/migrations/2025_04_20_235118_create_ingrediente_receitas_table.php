<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
         // Using diagram name
        Schema::create('ingrediente_receitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_receita')->constrained('receitas')->onDelete('cascade');
            $table->foreignId('id_produto')->constrained('produtos')->onDelete('restrict');
            $table->decimal('quantidade', 10, 3);
            $table->string('unidade', 50);
            $table->string('observacoes', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingrediente_receitas');
    }
};