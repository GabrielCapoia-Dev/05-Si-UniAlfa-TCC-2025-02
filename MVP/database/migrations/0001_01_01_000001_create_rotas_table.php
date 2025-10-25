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
        Schema::create('rotas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('turno', ['Manhã', 'Tarde', 'Noite', 'Integral']);
            $table->decimal('distancia_total', 8, 2)->nullable()->comment('km');
            $table->unsignedInteger('tempo_estimado')->nullable()->comment('min');
            $table->double('valor_por_km')->nullable();
            $table->double('valor_total')->nullable();
            $table->json('geometry')->nullable();
            $table->json('waypoints')->nullable();
            $table->json('legs')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rotas');
    }
};
