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
        Schema::create('valor_rota_mensals', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('ano');
            $table->json('valor_total_por_rota');
            $table->json('valor_total_por_turno');
            $table->integer('valor_total_mes');
            $table->timestamps();
            $table->unique(['mes', 'ano']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valor_rota_mensals');
    }
};
