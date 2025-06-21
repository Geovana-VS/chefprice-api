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
        Schema::table('receitas', function (Blueprint $table) {
            $table->decimal('custos_adicionais', 10, 2)->nullable()->after('tempo_preparo');
            $table->decimal('lucro_esperado', 10, 2)->nullable()->after('custos_adicionais');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receitas', function (Blueprint $table) {
            $table->dropColumn('custos_adicionais');
            $table->dropColumn('lucro_esperado');
        });
    }
};