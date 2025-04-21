<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_barra', 100)->nullable();
            $table->string('nome', 200);
            $table->string('descricao', 500)->nullable();
            $table->foreignId('id_categoria')->constrained('categorias')->onDelete('no action');
            $table->string('unidade_medida', 50)->nullable();

            $table->timestamps(3);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};