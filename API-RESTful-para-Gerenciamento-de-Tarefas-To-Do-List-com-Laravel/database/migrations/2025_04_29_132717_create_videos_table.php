<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->boolean('aumentar_largura_tela')->default(false); // Aumentar a largura da tela
            $table->enum('tipo_reproducao', ['automatico_sem_som', 'manual_com_som'])->default('automatico_sem_som'); // Tipo de reprodução
            $table->string('link_youtube'); // Link do YouTube
            $table->string('imagem_path'); // Caminho da imagem (1920px x 1080px)
            $table->string('titulo'); // Título do vídeo
            $table->string('descricao'); // Descrição do vídeo
            $table->string('texto_botao')->nullable(); // Texto do botão
            $table->string('link_botao')->nullable(); // Link do botão
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
