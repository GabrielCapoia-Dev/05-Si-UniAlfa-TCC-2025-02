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
        Schema::create('turmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_escola')->constrained('escolas')->cascadeOnDelete();
            $table->foreignId('id_serie')->nullable()->constrained('series')->nullOnDelete();
            $table->string('turma')->max(1)->unique();
            $table->string('turno');
            $table->timestamps();

            $table->unique(['id_escola', 'id_serie', 'turno', 'turma'], 'turma_unica_por_contexto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turmas');
    }
};
