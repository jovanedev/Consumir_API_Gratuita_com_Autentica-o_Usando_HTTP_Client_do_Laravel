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
        Schema::table('lojas', function (Blueprint $table) {
            $table->text('logomarca')->nullable();
            $table->string('categoria')->nullable();
            $table->text('url_loja')->nullable();
            $table->text('cor_principal')->nullable();
            $table->json('cores_auxiliares')->nullable();
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lojas', function (Blueprint $table) {
            $table->dropColumn([
                'logomarca',
                'categoria',
                'url_loja',
                'cor_principal',
                'cores_auxiliares',
                'facebook',
                'instagram',
            ]);
        });
    }
};
