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
        Schema::create('listas_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Chave estrangeira para a tabela de status
            $table->foreignId('id_lista_compra_status')
                  ->constrained('lista_compra_status');

            $table->string('nome_lista', 255);
            $table->text('descricao')->nullable();
            $table->timestamp('data_conclusao', 3)->nullable();
            $table->timestamps(3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listas_compras', function (Blueprint $table) {
            $table->dropForeign(['id_lista_compra_status']);
        });
        Schema::dropIfExists('listas_compras');
    }
};