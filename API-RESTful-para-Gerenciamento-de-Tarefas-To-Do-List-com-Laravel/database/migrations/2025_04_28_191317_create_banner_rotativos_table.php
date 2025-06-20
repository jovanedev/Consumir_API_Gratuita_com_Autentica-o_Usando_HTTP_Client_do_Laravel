<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banner_rotativos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('imagem_desktop'); // imagem para desktop
            $table->string('imagem_desktop_path'); // Caminho da imagem para desktop
            $table->string('imagem_mobile')->nullable(); // imagem para mobile (opcional)
            $table->string('imagem_mobile_path')->nullable(); // Caminho da imagem para mobile (opcional)
            $table->boolean('largura_tela')->default(false); // Ocupar largura total da tela
            $table->boolean('efeito_movimento')->default(false); // Efeito de movimento nas imagens
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_rotativos');
    }
};
