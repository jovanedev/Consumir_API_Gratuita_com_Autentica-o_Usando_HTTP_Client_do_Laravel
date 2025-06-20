<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos_em_oferta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('titulo')->default('Ofertas'); // Título da seção
            $table->string('tipo_visualizacao')->default('Carrossel'); // Tipo de visualização (Carrossel, Grade ou Lista)
            $table->integer('produtos_por_linha_celulares')->default(2); // Quantidade de produtos por linha em celulares
            $table->integer('produtos_por_linha_computadores')->default(4); // Quantidade de produtos por linha em computadores
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos_em_oferta');
    }
};
