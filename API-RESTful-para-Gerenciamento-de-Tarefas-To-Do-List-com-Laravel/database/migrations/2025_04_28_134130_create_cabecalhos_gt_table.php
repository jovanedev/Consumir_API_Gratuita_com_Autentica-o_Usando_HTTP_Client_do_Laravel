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
        Schema::create('cabecalhos_gt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('cor_fundo')->default('#ffffff'); // Cor de fundo
            $table->string('cor_texto_icones')->default('#000000'); // Cor dos textos e ícones
            $table->string('tamanho_logo')->default('pre-definido'); // Tamanho do logo
            $table->boolean('mostrar_idiomas')->default(false); // Mostrar idiomas e moedas
            $table->json('cabecalho_em_celulares')->nullable();
            $table->json('cabecalho_em_computadores')->nullable();
            $table->json('barra_anuncio')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabecalhos_gt');
    }
};
