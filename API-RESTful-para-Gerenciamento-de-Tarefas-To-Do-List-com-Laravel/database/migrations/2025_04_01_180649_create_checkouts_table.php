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
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->boolean('cores_layout')->default(false);
            $table->boolean('pedir_telefone')->default(false);
            $table->boolean('pedir_endereco')->default(false);
            $table->text('mensagem_cliente')->nullable();
            $table->text('mensagem_segmento')->nullable();
            $table->string('compra')->nullable();
            $table->boolean('checkout_acelerado')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};
