<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popup_promocionais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->boolean('mostrar_popup')->default(false); // Mostrar pop-up
            $table->string('imagem_path'); // Caminho da imagem (375px x 190px)
            $table->string('titulo'); // Título do pop-up
            $table->string('descricao'); // Descrição do pop-up
            $table->string('texto_botao'); // Texto do botão
            $table->string('link_botao'); // Link do botão
            $table->boolean('permitir_inscricao_newsletter')->default(false); // Permitir inscrição na newsletter
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popup_promocionais');
    }
};
