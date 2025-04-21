<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produto_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('id_produto')->constrained('produtos')->onDelete('cascade'); 
            $table->decimal('preco_unitario', 10, 2)->nullable();
            $table->decimal('quantidade', 10, 3)->nullable();
            $table->decimal('preco_total', 10, 2)->nullable();
            $table->date('data_compra')->nullable();
            $table->decimal('desconto', 10, 2)->nullable();
            $table->timestamps(3);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_historicos');
    }
};