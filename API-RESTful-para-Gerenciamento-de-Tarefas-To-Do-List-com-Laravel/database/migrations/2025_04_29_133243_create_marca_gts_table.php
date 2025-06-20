<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marca_gts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('tipo_visualizacao')->default('Carrossel'); // Tipo de visualização (Carrossel, Grade ou Lista)
            $table->string('titulo')->default('Nossas marcas'); // Título da seção
            $table->string('imagem_path'); // Caminho da imagem (200px x 200px)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marca_gts');
    }
};
