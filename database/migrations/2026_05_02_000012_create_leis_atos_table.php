<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leis_atos', function (Blueprint $table) {
            $table->id();
            $table->string('numero');
            $table->enum('tipo', ['lei', 'decreto', 'resolucao', 'ato_gestor']);
            $table->date('data_ato');
            $table->date('data_publicacao');
            $table->text('descricao')->nullable();
            $table->string('arquivo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leis_atos');
    }
};
