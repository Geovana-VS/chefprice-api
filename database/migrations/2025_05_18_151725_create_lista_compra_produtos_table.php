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
        Schema::create('lista_compra_produtos', function (Blueprint $table) {
            $table->foreignId('id_lista_compra')
                  ->constrained('listas_compras')
                  ->onDelete('cascade');
            $table->foreignId('id_produto')
                  ->constrained('produtos')
                  ->onDelete('cascade');
            $table->decimal('quantidade', 10, 3);
            $table->string('unidade_medida', 50)->nullable();
            $table->text('observacao')->nullable();
            $table->boolean('comprado')->default(false);
            $table->timestamps(3);

            $table->primary(['id_lista_compra', 'id_produto']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lista_compra_produtos');
    }
};