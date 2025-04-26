<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Import DB facade if needed for data migration

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imagens', function (Blueprint $table) {

            $table->text('url')->nullable()->after('id_tipo_imagem');
            $table->string('caminho_storage', 255)->nullable()->after('nome_arquivo');

            if (Schema::hasColumn('imagens', 'dados_imagem')) {
                DB::statement('ALTER TABLE imagens DROP COLUMN dados_imagem');
            }

            if (Schema::hasColumn('imagens', 'nome_arquivo_storage')) {
                $table->dropColumn('nome_arquivo_storage');
            }
        });
    }

    /**
     * Reverse the migrations.

     */
    public function down(): void
    {
        Schema::table('imagens', function (Blueprint $table) {

            if (!Schema::hasColumn('imagens', 'nome_arquivo_storage')) {
                $table->string('nome_arquivo_storage', 255)->nullable()->after('nome_arquivo');
            }
            if (!Schema::hasColumn('imagens', 'dados_imagem')) {
                $table->binary('dados_imagem')->nullable()->after('id_tipo_imagem');
            }

            if (Schema::hasColumn('imagens', 'caminho_storage')) {
                $table->dropColumn('caminho_storage');
            }
            if (Schema::hasColumn('imagens', 'url')) {
                $table->dropColumn('url');
            }
        });
    }
};
