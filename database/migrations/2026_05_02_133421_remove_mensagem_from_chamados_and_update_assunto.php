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
        Schema::table('chamados', function (Blueprint $table) {
            // Alterar assunto de string para text (campo grande)
            $table->text('assunto')->change();

            // Remover campo mensagem (não é mais usado)
            $table->dropColumn('mensagem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->string('assunto', 255)->change();
            $table->text('mensagem')->after('assunto');
        });
    }
};
