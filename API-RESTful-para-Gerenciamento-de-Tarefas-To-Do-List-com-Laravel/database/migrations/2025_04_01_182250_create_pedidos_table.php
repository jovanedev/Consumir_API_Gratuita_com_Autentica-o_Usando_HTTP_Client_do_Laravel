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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->text('codigo_unico_pedido')->unique();
            $table->enum('status', ['pendente', 'pago', 'enviado', 'entregue', 'cancelado'])->default('pendente');
            $table->decimal('valor_total', 10, 2);
            $table->decimal('valor_desconto', 10, 2)->default(0);
            $table->decimal('frete', 10, 2)->default(0);
            $table->enum('tipo_frete', ['normal', 'expresso', 'retirada'])->default('normal');
            $table->integer('prazo_entrega')->default(7);
            $table->foreignId('endereco_entrega_id')->constrained('enderecos')->onDelete('cascade');
            $table->foreignId('metodo_pagamento')->constrained('forma_pagamentos')->onDelete('cascade');
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
