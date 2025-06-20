<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nome');
            $table->date('data_nascimento');
            $table->enum('genero', ['masculino', 'feminino', 'outro']);
            $table->enum('documento_tipo', ['BI', 'Passaporte', 'Outro']);
            $table->string('documento_numero');
            $table->foreignId('endereco_id')->constrained('enderecos')->onDelete('cascade');
            $table->enum('status', ['ativo', 'inativo'])->default('ativo');
            $table->timestamp('criado_em')->useCurrent();
            $table->timestamp('atualizado_em')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
