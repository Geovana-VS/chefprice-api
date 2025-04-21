<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('imagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('users')->onDelete('no action');
            $table->foreignId('id_tipo_imagem')->constrained('tipo_imagens')->onDelete('no action');
            $table->binary('dados_imagem');
            $table->string('nome_arquivo', 255)->nullable();
            $table->string('nome_arquivo_storage', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->boolean('is_publico')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagens');
    }
};