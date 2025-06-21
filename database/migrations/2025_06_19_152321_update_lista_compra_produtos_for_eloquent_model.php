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
        Schema::table('lista_compra_produtos', function (Blueprint $table) {

            $table->dropPrimary(['id_lista_compra', 'id_produto']);
            $table->id()->first();
            $table->unique(['id_lista_compra', 'id_produto']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lista_compra_produtos', function (Blueprint $table) {
            $table->dropUnique(['id_lista_compra', 'id_produto']);
            $table->dropColumn('id');
            $table->primary(['id_lista_compra', 'id_produto']);
        });
    }
};