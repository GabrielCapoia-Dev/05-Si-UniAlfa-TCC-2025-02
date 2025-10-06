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
            $table->foreignId('id_rota')->constrained('rotas')->cascadeOnDelete();
            $table->foreignId('id_escola')->nullable()->constrained('escolas')->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->unsignedInteger('ordem');
            $table->enum('tipo', ['ponto', 'escola']);

            $table->timestamps();
            $table->index('id_rota');
            $table->unique(['id_rota', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pontos_de_parada');
    }
};
