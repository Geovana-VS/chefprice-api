<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('etapa_receitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_receita')->constrained('receitas')->onDelete('cascade');
            $table->integer('numero_etapa'); 
            $table->text('instrucoes');
            $table->unique(['id_receita', 'numero_etapa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etapa_receitas');
    }
};