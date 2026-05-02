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
        Schema::table('tramites_solicitacao', function (Blueprint $table) {
            $table->string('origem')->nullable()->after('fase');
            $table->string('destino')->nullable()->after('origem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tramites_solicitacao', function (Blueprint $table) {
            $table->dropColumn(['origem', 'destino']);
        });
    }
};
