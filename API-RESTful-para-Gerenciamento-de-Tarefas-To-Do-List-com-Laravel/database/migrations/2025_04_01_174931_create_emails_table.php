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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->string('descricao');
            $table->json('conteudo');
            $table->json('conteudo_html');
            $table->enum('status_conteudo_html', ['false', 'true'])->default('false');
            $table->enum('tipo', [
                'ativacao_conta', 'mudanca_senha', 'boas_vindas', 'cancelamento_compra',
                'confirmacao_pagamento', 'confirmacao_compra', 'confirmacao_envio',
                'carrinhos_abandonados'
            ]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
