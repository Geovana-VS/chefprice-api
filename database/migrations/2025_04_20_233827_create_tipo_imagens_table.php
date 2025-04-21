<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tipo_imagens', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_imagens');
    }
};