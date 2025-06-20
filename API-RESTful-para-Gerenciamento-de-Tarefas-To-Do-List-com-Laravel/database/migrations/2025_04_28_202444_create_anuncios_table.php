<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anuncios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('titulo'); // Título do anúncio
            $table->string('texto'); // Texto do anúncio
            $table->string('link')->nullable(); // Link para redirecionamento (opcional)
            $table->string('imagem_desktop'); // Caminho da imagem para desktop
            $table->string('imagem_desktop_path'); // Caminho da imagem para desktop
            $table->string('imagem_mobile')->nullable(); // Caminho da imagem para mobile (opcional)
            $table->string('imagem_mobile_path')->nullable(); // Caminho da imagem para mobile (opcional)
            $table->boolean('carregar_imagens_mobile')->default(false); // Carregar imagens diferentes para mobile
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anuncios');
    }
};
