<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pontos_de_parada', function (Blueprint $table) {
            $table->id();
            
            // Informações básicas
            $table->string('nome');
            $table->text('descricao')->nullable();
            
            // Localização
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('raio')->default(500); // em metros
            
            // Endereço
            $table->string('logradouro');
            $table->string('bairro');
            $table->string('cidade');
            $table->string('uf', 2);
            $table->string('cep', 9);
            
            // Classificação
            $table->enum('tipo', [
                'terminal',
                'parada_simples', 
                'ponto_comercial',
                'ponto_residencial',
                'ponto_industrial'
            ])->default('parada_simples');
            
            // Status
            $table->boolean('ativo')->default(true);
            
            // Timestamps
            $table->timestamps();
            
            // Índices para otimização de consultas geográficas
            $table->index(['latitude', 'longitude']);
            $table->index(['cidade', 'uf']);
            $table->index(['ativo', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pontos_de_parada');
    }
};