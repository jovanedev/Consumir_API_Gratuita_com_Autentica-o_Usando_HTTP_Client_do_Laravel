<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrinhos_gts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->boolean('mostrar_botao_ver_mais')->default(true); // Mostrar botão "Ver mais produtos"
            $table->decimal('valor_minimo_compra', 10, 2)->default(3000.00); // Valor mínimo de compra
            $table->boolean('carrinho_rapido')->default(true); // Carrinho de compra rápida
            $table->boolean('sugerir_produtos_complementares')->default(true); // Sugerir produtos complementares
            $table->boolean('mostrar_calculadora_frete')->default(true); // Mostrar calculadora de frete e lojas físicas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrinhos_gts');
    }
};
