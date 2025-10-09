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
        Schema::create('alunos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->foreignId('id_rota')->nullable()->constrained('rotas')->nullOnDelete();
            $table->foreignId('id_turma')->constrained('turmas')->restrictOnDelete();
            $table->date('data_nascimento');
            $table->string('cgm')->unique();
            $table->enum('sexo', ['Masculino', "Feminino"]);
            $table->string('nome_responsavel');
            $table->string('telefone_responsavel');
            $table->string('telefone_aluno')->nullable();
            $table->string('foto')->nullable()->unique();
            $table->string('telefone_alternativo')->nullable();
            $table->decimal('latitude')->nullable();
            $table->decimal('longitude')->nullable();
            $table->integer('raio')->nullable();
            $table->string('logradouro');
            $table->string('bairro');
            $table->string('cidade');
            $table->string('estado');
            $table->string('cep')->max(8);
            $table->string('numero')->nullable();
            $table->string('complemento')->nullable();
            $table->boolean('tem_carteirinha')->default(false);
            $table->timestamps();

            $table->unique(['id_turma', 'cgm']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alunos');
    }
};