<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela de layouts SIMAM
        Schema::create('simam_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name', 150);
            $table->string('module', 100);
            $table->string('generation_type', 50);
            $table->integer('order_index')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['module', 'active']);
            $table->index(['generation_type']);
        });

        // Tabela de exportações
        Schema::create('simam_exports', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->string('module', 100);
            $table->string('generation_type', 50);
            $table->boolean('only_active')->default(true);
            $table->string('zip_name')->nullable();
            $table->string('zip_path')->nullable();
            $table->enum('status', ['processando', 'sucesso', 'erro'])->default('processando');
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['year', 'month', 'module']);
            $table->index('status');
        });

        // Tabela de arquivos gerados
        Schema::create('simam_generated_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_id')->constrained('simam_exports')->cascadeOnDelete();
            $table->string('layout_key', 100);
            $table->string('file_name');
            $table->enum('status', ['processando', 'gerado', 'erro'])->default('processando');
            $table->integer('records_count')->default(0);
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('export_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simam_generated_files');
        Schema::dropIfExists('simam_exports');
        Schema::dropIfExists('simam_layouts');
    }
};
