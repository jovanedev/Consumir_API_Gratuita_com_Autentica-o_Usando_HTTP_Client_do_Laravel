<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('textos_gts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('titulo'); // Título do texto
            $table->text('conteudo'); // Conteúdo do texto
            $table->string('tipo_texto')->default('Texto Geral'); // Tipo de texto (ex.: Cabeçalho, Rodapé, Banner)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('textos_gts');
    }
};
