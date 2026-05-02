<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empenhos', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->date('data_emissao');
            $table->decimal('valor', 15, 2);
            $table->decimal('saldo', 15, 2);
            $table->enum('status', ['disponivel', 'bloqueado', 'sem_saldo'])->default('disponivel');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empenhos');
    }
};
