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
            $table->string('navegador')->nullable()->after('status');
            $table->string('sistema_operacional')->nullable()->after('navegador');
            $table->string('ip_origem', 45)->nullable()->after('sistema_operacional'); // IPv6 support
            $table->text('user_agent')->nullable()->after('ip_origem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->dropColumn(['navegador', 'sistema_operacional', 'ip_origem', 'user_agent']);
        });
    }
};
