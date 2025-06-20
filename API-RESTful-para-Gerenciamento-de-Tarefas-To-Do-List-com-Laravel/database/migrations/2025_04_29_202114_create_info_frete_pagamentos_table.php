<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('info_frete_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->boolean('usar_cores_secao')->default(false); // Usar cores para a seção
            $table->string('cor_fundo')->nullable(); // Cor de fundo (ex.: #FFFFFF)
            $table->string('cor_texto')->nullable(); // Cor de texto (ex.: #000000)
            $table->boolean('mostrar_banners_home')->default(false); // Mostrar banners na home
            $table->string('imagem_path'); // Caminho da imagem (120px x 120px)
            $table->string('icone'); // Ícone (ex.: Frete, Pagamento, etc.)
            $table->string('titulo'); // Título do banner
            $table->string('descricao'); // Descrição do banner
            $table->string('link'); // Link do banner
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('info_frete_pagamentos');
    }
};
