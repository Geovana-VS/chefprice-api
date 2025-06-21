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
        Schema::create('colecao_receitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->string('nome', 255);
            $table->text('descricao')->nullable();
            $table->timestamps(3); // Precisão de milissegundos, se desejado
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colecao_receitas');
    }
};