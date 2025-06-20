<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners_categorias_gts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained('categoria')->onDelete('cascade');
            $table->string('titulo')->nullable(); // Título para os banners de categorias
            $table->boolean('mostrar_texto_fora_imagem')->default(false); // Mostrar texto fora da imagem
            $table->boolean('mostrar_banners_carrossel')->default(false); // Mostrar banners dentro de um carrossel
            $table->boolean('mesma_altura_banners')->default(false); // Usar a mesma altura para todos os banners
            $table->boolean('remover_espacos_banners')->default(false); // Remover espaços entre os banners
            $table->integer('banners_por_linha')->default(4); // Disposição: 4 banners por linha
            $table->string('imagem_path_computador'); // Caminho da imagem para computadores (1920px x 900px)
            $table->boolean('carregar_imagens_celular')->default(false); // Carregar outras imagens para celulares
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners_categorias_gts');
    }
};
