<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf', 14)->unique()->nullable()->after('email');
            $table->enum('perfil', [
                'responsavel_tecnico',
                'gestor_contrato',
                'operador_pmsjp',
                'gestor_suporte',
                'usuario_comum',
                'operador_orcamentario'
            ])->default('usuario_comum')->after('cpf');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cpf', 'perfil']);
        });
    }
};
