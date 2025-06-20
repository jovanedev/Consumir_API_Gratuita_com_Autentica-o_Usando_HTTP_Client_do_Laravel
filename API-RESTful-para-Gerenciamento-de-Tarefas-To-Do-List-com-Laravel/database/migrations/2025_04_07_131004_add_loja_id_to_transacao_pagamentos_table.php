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
        Schema::table('transacao_pagamentos', function (Blueprint $table) {
            // Especificando em que posição ela será criada
            $table->unsignedBigInteger('loja_id')->nullable()->after('id');
            
            // Definindo a chave estrangeira
            $table->foreign('loja_id')->references('id')->on('lojas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transacao_pagamentos', function (Blueprint $table) {
            // Especificando em que posição ela será criada
            $table->unsignedBigInteger('loja_id')->nullable()->after('id');
            
            // Definindo a chave estrangeira
            $table->foreign('loja_id')->references('id')->on('lojas')->onDelete('set null');
        });
    }
};
