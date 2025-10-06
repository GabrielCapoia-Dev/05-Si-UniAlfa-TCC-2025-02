<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escola_rota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escola_id')->constrained('escolas')->cascadeOnDelete();
            $table->foreignId('rota_id')->constrained('rotas')->cascadeOnDelete();

            $table->unique(['escola_id', 'rota_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escola_rota');
    }
};
