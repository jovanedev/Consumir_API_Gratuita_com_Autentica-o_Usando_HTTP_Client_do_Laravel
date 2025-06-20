<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depoimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->boolean('usar_cores_secao')->default(false);
            $table->string('cor_fundo')->nullable();
            $table->string('cor_texto')->nullable();
            $table->boolean('mostrar_banners_home')->default(false);
            $table->string('imagem_path');
            $table->string('icone');
            $table->string('titulo');
            $table->string('descricao');
            $table->string('link');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depoimentos');
    }
};
