<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cache
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // Auth / sessions
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Queue jobs
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // Plans
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->longText('features')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });

        // Users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('cpf', 14)->unique()->nullable();
            $table->string('perfil', 30)->default('usuario_comum');
            $table->string('password');
            $table->tinyInteger('is_active')->default(1);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Personal access tokens
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        // User subscriptions
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['plan_id', 'status', 'expires_at']);
        });

        // Audit logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('entity');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->longText('payload')->nullable();
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity', 'entity_id']);
            $table->index('action');
            $table->index('user_id');
            $table->index('created_at');
        });

        // Fornecedores
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cnpj', 18)->unique();
            $table->foreignId('responsavel_tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // fornecedor_id on users (added after fornecedores exists)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('fornecedor_id')->nullable()->after('perfil')->constrained('fornecedores')->nullOnDelete();
        });

        // Contratos
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('fornecedor_id')->constrained('fornecedores')->cascadeOnDelete();
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->text('objeto')->nullable();
            $table->timestamps();
        });

        // Empenhos
        Schema::create('empenhos', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->date('data_emissao');
            $table->decimal('valor', 15, 2);
            $table->decimal('saldo', 15, 2);
            $table->string('status', 20)->default('disponivel');
            $table->timestamps();
        });

        // Solicitações de pagamento
        Schema::create('solicitacoes_pagamento', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('empenho_id')->constrained('empenhos')->cascadeOnDelete();
            $table->foreignId('solicitante_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('valor', 15, 2);
            $table->text('observacao')->nullable();

            $table->string('tipo_documento');
            $table->string('numero_documento');
            $table->string('serie')->nullable();
            $table->date('data_emissao_documento');
            $table->text('observacao_documento')->nullable();

            $table->string('forma_pagamento', 20)->default('conta_bancaria');
            $table->string('banco')->nullable();
            $table->string('agencia')->nullable();
            $table->string('digito_agencia')->nullable();
            $table->string('conta')->nullable();
            $table->string('digito_conta')->nullable();
            $table->string('operacao')->nullable();
            $table->string('cidade_banco')->nullable();
            $table->text('observacao_pagamento')->nullable();

            $table->string('status', 40)->default('rascunho');

            $table->date('cancelada_em')->nullable();
            $table->text('motivo_cancelamento')->nullable();
            $table->date('paga_em')->nullable();

            $table->timestamps();
        });

        // Anexos de solicitação
        Schema::create('anexos_solicitacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_id')->constrained('solicitacoes_pagamento')->cascadeOnDelete();
            $table->string('tipo_anexo', 40);
            $table->string('arquivo_path')->nullable();
            $table->string('arquivo_nome')->nullable();
            $table->string('status', 30)->default('pendente');
            $table->date('data_envio')->nullable();
            $table->text('motivo_recusa')->nullable();
            $table->foreignId('enviado_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enviado_em')->nullable();
            $table->foreignId('aprovado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('data_aprovacao')->nullable();
            $table->timestamps();
        });

        // Trâmites de solicitação
        Schema::create('tramites_solicitacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_id')->constrained('solicitacoes_pagamento')->cascadeOnDelete();
            $table->string('fase');
            $table->string('origem')->nullable();
            $table->string('destino')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacao')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamps();
        });

        // Chamados
        Schema::create('chamados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('modulo');
            $table->text('assunto');
            $table->string('status', 20)->default('aberto');
            $table->string('navegador')->nullable();
            $table->string('sistema_operacional')->nullable();
            $table->string('ip_origem', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('data_ultima_resposta')->nullable();
            $table->timestamp('data_conclusao')->nullable();
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Mensagens de chamado
        Schema::create('mensagens_chamado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('chamados')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 20)->default('resposta');
            $table->text('mensagem');
            $table->timestamps();
        });

        // Anexos de chamado
        Schema::create('anexos_chamado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('chamados')->cascadeOnDelete();
            $table->foreignId('mensagem_id')->nullable()->constrained('mensagens_chamado')->cascadeOnDelete();
            $table->string('caminho');
            $table->string('nome_original');
            $table->string('nome_salvo');
            $table->bigInteger('tamanho');
            $table->string('tipo', 100);
            $table->foreignId('enviado_por_usuario_id')->constrained('users');
            $table->timestamps();
        });

        // Exportações de prestação de contas (legado)
        Schema::create('exportacoes_prestacao_contas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->integer('ano');
            $table->string('modulo');
            $table->string('tipo_geracao');
            $table->integer('mes')->nullable();
            $table->longText('arquivos_selecionados');
            $table->string('arquivo_gerado');
            $table->integer('quantidade_registros');
            $table->timestamps();
        });

        // Leis e atos
        Schema::create('leis_atos', function (Blueprint $table) {
            $table->id();
            $table->string('numero');
            $table->string('tipo', 20);
            $table->date('data_ato');
            $table->date('data_publicacao');
            $table->text('descricao')->nullable();
            $table->string('arquivo')->nullable();
            $table->timestamps();
        });

        // Alterações orçamentárias
        Schema::create('alteracoes_orcamentarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lei_ato_id')->constrained('leis_atos')->cascadeOnDelete();
            $table->string('decreto_autorizador');
            $table->date('data_ato');
            $table->date('data_publicacao');
            $table->string('tipo_ato', 20);
            $table->string('tipo_credito', 20);
            $table->string('tipo_recurso', 30);
            $table->decimal('valor_credito', 15, 2);
            $table->timestamps();
        });

        // Dotações alteradas
        Schema::create('dotacoes_alteradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alteracao_orcamentaria_id')->constrained('alteracoes_orcamentarias')->cascadeOnDelete();
            $table->string('dotacao_orcamentaria');
            $table->string('conta_receita')->nullable();
            $table->decimal('valor_suprimido', 15, 2)->default(0);
            $table->decimal('valor_suplementado', 15, 2)->default(0);
            $table->decimal('saldo_atual', 15, 2)->default(0);
            $table->decimal('novo_saldo', 15, 2)->default(0);
            $table->timestamps();
        });

        // SIMAM layouts
        Schema::create('simam_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name', 150);
            $table->string('module', 100);
            $table->string('generation_type', 50);
            $table->integer('order_index')->default(0);
            $table->tinyInteger('active')->default(1);
            $table->timestamps();

            $table->index(['module', 'active']);
            $table->index('generation_type');
        });

        // SIMAM exports
        Schema::create('simam_exports', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->string('module', 100);
            $table->string('generation_type', 50);
            $table->tinyInteger('only_active')->default(1);
            $table->string('zip_name')->nullable();
            $table->string('zip_path')->nullable();
            $table->string('status', 20)->default('processando');
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['year', 'month', 'module']);
            $table->index('status');
        });

        // SIMAM generated files
        Schema::create('simam_generated_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_id')->constrained('simam_exports')->cascadeOnDelete();
            $table->string('layout_key', 100);
            $table->string('file_name');
            $table->string('status', 20)->default('processando');
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
        Schema::dropIfExists('dotacoes_alteradas');
        Schema::dropIfExists('alteracoes_orcamentarias');
        Schema::dropIfExists('leis_atos');
        Schema::dropIfExists('exportacoes_prestacao_contas');
        Schema::dropIfExists('anexos_chamado');
        Schema::dropIfExists('mensagens_chamado');
        Schema::dropIfExists('chamados');
        Schema::dropIfExists('tramites_solicitacao');
        Schema::dropIfExists('anexos_solicitacao');
        Schema::dropIfExists('solicitacoes_pagamento');
        Schema::dropIfExists('empenhos');
        Schema::dropIfExists('contratos');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['fornecedor_id']);
            $table->dropColumn('fornecedor_id');
        });
        Schema::dropIfExists('fornecedores');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};
