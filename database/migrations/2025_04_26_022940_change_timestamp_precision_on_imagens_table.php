<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imagens', function (Blueprint $table) {
            if (Schema::hasColumn('imagens', 'created_at')) {
                $table->dateTime('created_at', precision: 3)->nullable()->change();
            }
            if (Schema::hasColumn('imagens', 'updated_at')) {
                $table->dateTime('updated_at', precision: 3)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('imagens', function (Blueprint $table) {
             if (Schema::hasColumn('imagens', 'created_at')) {
                $table->timestamp('created_at')->nullable()->change();
            }
            if (Schema::hasColumn('imagens', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->change();
            }
        });
    }
};
