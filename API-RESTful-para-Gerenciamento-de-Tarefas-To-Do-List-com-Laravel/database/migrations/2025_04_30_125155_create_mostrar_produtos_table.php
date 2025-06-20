<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mostrar_produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->boolean('mostrar_calculadora_frete')->default(false); // Mostrar calculadora de frete e lojas físicas
            $table->boolean('mostrar_parcelas')->default(false); // Mostrar parcelas na página de produto
            $table->boolean('mostrar_preco_desconto')->default(false); // Mostrar preço com maior desconto
            $table->boolean('variacoes_como_botoes')->default(false); // Mostrar variações como botões
            $table->boolean('variacoes_cor_como_foto')->default(false); // Mostrar foto da variação de cor como botão
            $table->string('link_guia_medidas')->nullable(); // Link para guia de medidas
            $table->boolean('mostrar_estoque')->default(false); // Mostrar estoque disponível
            $table->boolean('mostrar_mensagem_ultima_unidade')->default(false); // Mostrar mensagem para última unidade
            $table->string('mensagem_ultima_unidade')->default('Atenção, última peça!'); // Mensagem para última unidade
            $table->boolean('descricao_largura_total')->default(false); // Descrição ocupando toda a largura (computadores)
            $table->boolean('permitir_comentarios_facebook')->default(false); // Permitir comentários via Facebook
            $table->string('facebook_perfil_id')->nullable(); // ID do perfil do Facebook
            $table->string('titulo_produtos_alternativos')->default('Produtos similares'); // Título para produtos alternativos
            $table->string('titulo_produtos_complementares')->default('Para comprar com esse produto'); // Título para produtos complementares
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mostrar_produtos');
    }
};
