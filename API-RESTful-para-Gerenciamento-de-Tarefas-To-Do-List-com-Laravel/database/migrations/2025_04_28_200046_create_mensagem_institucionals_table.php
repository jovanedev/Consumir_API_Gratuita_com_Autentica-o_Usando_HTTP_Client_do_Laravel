<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensagem_institucionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('subtitulo')->nullable(); // Subtítulo
            $table->string('titulo'); // Título
            $table->boolean('titulo_italico')->default(false); // Usar texto em itálico para o título
            $table->string('link')->nullable(); // Link
            $table->string('botao')->nullable(); // Texto do botão
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensagem_institucionals');
    }
};
