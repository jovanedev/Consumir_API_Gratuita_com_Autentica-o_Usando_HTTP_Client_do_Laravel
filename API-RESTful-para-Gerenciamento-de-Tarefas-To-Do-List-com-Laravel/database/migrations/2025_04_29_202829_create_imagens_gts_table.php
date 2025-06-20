<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imagens_gts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('imagem_path'); // Caminho da imagem (assumindo 1200px x 600px)
            $table->string('titulo')->nullable(); // Título opcional para a imagem
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagens_gts');
    }
};
