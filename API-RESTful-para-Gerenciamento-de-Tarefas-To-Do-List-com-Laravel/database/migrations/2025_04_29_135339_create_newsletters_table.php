<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->boolean('aumentar_largura_tela')->default(false); // Aumentar a largura da tela
            $table->boolean('usar_cores_newsletter')->default(false); // Usar cores para a newsletter
            $table->string('cor_fundo')->nullable(); // Cor de fundo (ex.: #FFFFFF)
            $table->string('cor_texto')->nullable(); // Cor de texto (ex.: #000000)
            $table->string('imagem_path'); // Caminho da imagem (800px x 480px)
            $table->string('titulo')->default('Newsletter'); // Título da seção
            $table->string('descricao')->default('Cadastre-se e receba nossas ofertas.'); // Descrição da seção
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletters');
    }
};
