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
        Schema::table('anexos_solicitacao', function (Blueprint $table) {
            $table->foreignId('enviado_por_usuario_id')->nullable()->after('motivo_recusa')->constrained('users')->nullOnDelete();
            $table->timestamp('enviado_em')->nullable()->after('enviado_por_usuario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anexos_solicitacao', function (Blueprint $table) {
            $table->dropForeign(['enviado_por_usuario_id']);
            $table->dropColumn(['enviado_por_usuario_id', 'enviado_em']);
        });
    }
};
