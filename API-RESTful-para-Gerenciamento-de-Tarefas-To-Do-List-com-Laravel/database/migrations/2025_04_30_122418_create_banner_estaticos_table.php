<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners_estaticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('imagem_path'); // Caminho da imagem (assumindo 1920px x 600px)
            $table->string('titulo')->nullable(); // Título do banner
            $table->string('link')->nullable(); // Link associado ao banner
            $table->boolean('exibir')->default(true); // Controla se o banner é exibido
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners_estaticos');
    }
};
