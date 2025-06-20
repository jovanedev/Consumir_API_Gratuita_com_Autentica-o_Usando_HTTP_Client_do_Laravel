<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkouts_gts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->boolean('exibir_opcoes_entrega')->default(true); // Exibir opções de entrega
            $table->boolean('exibir_opcoes_pagamento')->default(true); // Exibir opções de pagamento
            $table->boolean('exibir_resumo_pedido')->default(true); // Exibir resumo do pedido
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkouts_gts');
    }
};
