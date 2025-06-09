<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('colecao_receita_receita', function (Blueprint $table) {
            $table->foreignId('id_colecao_receita')
                  ->constrained('colecao_receitas')
                  ->onDelete('cascade');
            $table->foreignId('id_receita')
                  ->constrained('receitas')
                  ->onDelete('cascade');
            
            $table->primary(['id_colecao_receita', 'id_receita']);
            $table->timestamps(3); // Opcional: para rastrear quando a associação foi criada/atualizada
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colecao_receita_receita');
    }
};