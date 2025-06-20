<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('referencia')->unique();
            $table->text('codigo_unico_produto')->unique();
            $table->string('codigo_barras')->nullable();
            $table->float('preco_compra');
            $table->float('preco_venda');
            $table->float('iva')->nullable();
            $table->enum('gerir_stock', ['sim', 'nao'])->default('sim');
            $table->float('preco_promocional')->nullable();
            $table->foreignId('categoria_id')->constrained('categoria')->onDelete('cascade');
            $table->foreignId('marca_id')->constrained('marcas')->onDelete('cascade');
            $table->foreignId('fornecedor_id')->constrained('fornecedores')->onDelete('cascade');
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->decimal('peso', 10, 2)->nullable();
            $table->decimal('largura', 10, 2)->nullable();
            $table->decimal('altura', 10, 2)->nullable();
            $table->decimal('comprimento', 10, 2)->nullable();
            $table->string('foto_capa')->nullable();
            $table->json('imagens')->nullable();
            $table->string('video_url')->nullable();
            $table->enum('status', ['ativo', 'inativo'])->default('ativo');
            $table->boolean('destaque')->default(false);
            $table->boolean('novidade')->default(false);
            $table->boolean('produto_em_oferta')->default(false);
            $table->boolean('frete_gratis')->default(false);
            $table->integer('prazo_envio')->nullable();
            $table->boolean('variacoes')->default(false);
            $table->foreignId('desconto_id')->nullable()->constrained('descontos')->onDelete('set null');
            $table->integer('visualizacoes')->default(0);
            $table->decimal('avaliacao_media', 3, 2)->default(0);
            $table->integer('qtd_avaliacoes')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
