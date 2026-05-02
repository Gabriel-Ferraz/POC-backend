# ✅ IMPLEMENTADO: Responsável e Data de Envio dos Anexos

## 🎯 OBJETIVO

Adicionar informações de **quem enviou** o anexo e **quando foi enviado** na resposta da API de anexos.

**Status:** ✅ IMPLEMENTADO E TESTADO

---

## 📡 ENDPOINTS ATUALIZADOS

### 1. GET /api/solicitacoes/{id}/anexos

**Response completo:**

```json
{
  "solicitacao": {
    "id": 1,
    "numero": "SP-2024-000001",
    "status": "Aguardando Aprovação dos Anexos",
    "documento_fiscal_recusado": false,
    "anexos": [
      {
        "id": 1,
        "tipo_anexo": "documento_fiscal",
        "tipo_anexo_label": "Documento Fiscal (NF, Recibo, Guias, Faturas, etc.)",
        "arquivo_path": "/storage/anexos/demo/documento_fiscal.pdf",
        "arquivo_nome": "documento_fiscal.pdf",
        "status": "Aguardando Aprovação",
        "data_envio": "2024-01-25",
        "motivo_recusa": null,
        "is_documento_fiscal": true,
        "pode_reenviar": false,
        "pode_remover": false,
        
        // ✅ NOVOS CAMPOS
        "enviado_por": "João Silva (Responsável Técnico)",
        "enviado_em": "25/01/2024 09:30"
      },
      {
        "id": 2,
        "tipo_anexo": "certidao_negativa_debitos",
        "tipo_anexo_label": "Certidão Negativa de Débitos",
        "arquivo_path": "/storage/anexos/demo/certidao_negativa_debitos.pdf",
        "arquivo_nome": "certidao_negativa_debitos.pdf",
        "status": "Aguardando Aprovação",
        "data_envio": "2024-01-25",
        "motivo_recusa": null,
        "is_documento_fiscal": false,
        "pode_reenviar": false,
        "pode_remover": false,
        
        // ✅ NOVOS CAMPOS
        "enviado_por": "João Silva (Responsável Técnico)",
        "enviado_em": "25/01/2024 09:30"
      }
    ]
  }
}
```

---

### 2. GET /api/solicitacoes/{id}

**Response completo (com anexos inclusos):**

```json
{
  "solicitacao": {
    "id": 1,
    "numero": "SP-2024-000001",
    "status": "aguardando_aprovacao_anexos",
    "status_label": "Aguardando Aprovação dos Anexos",
    
    "anexos": [
      {
        "id": 1,
        "tipo_anexo": "documento_fiscal",
        "tipo_anexo_label": "Documento Fiscal (NF, Recibo, Guias, Faturas, etc.)",
        "arquivo_nome": "documento_fiscal.pdf",
        "arquivo_path": "/storage/anexos/demo/documento_fiscal.pdf",
        "status": "Aguardando Aprovação",
        "data_envio": "25/01/2024",
        "avaliado_por": null,
        "motivo_recusa": null,
        
        // ✅ NOVOS CAMPOS
        "enviado_por": "João Silva (Responsável Técnico)",
        "enviado_em": "25/01/2024 09:30"
      }
    ]
  }
}
```

---

## 🔧 IMPLEMENTAÇÃO REALIZADA

### 1. ✅ Migration Criada

**Arquivo:** `2026_05_02_115354_add_enviado_por_to_anexos_solicitacao_table.php`

```php
Schema::table('anexos_solicitacao', function (Blueprint $table) {
    $table->foreignId('enviado_por_usuario_id')
        ->nullable()
        ->after('motivo_recusa')
        ->constrained('users')
        ->nullOnDelete();
    
    $table->timestamp('enviado_em')
        ->nullable()
        ->after('enviado_por_usuario_id');
});
```

**Campos adicionados:**
- `enviado_por_usuario_id` (BIGINT, NULL) - FK para `users.id`
- `enviado_em` (TIMESTAMP, NULL) - Data/hora do envio

---

### 2. ✅ Model Atualizado

**Arquivo:** `app/Models/AnexoSolicitacao.php`

```php
protected $fillable = [
    'solicitacao_id',
    'tipo_anexo',
    'arquivo_path',
    'arquivo_nome',
    'status',
    'data_envio',
    'motivo_recusa',
    'aprovado_por',
    'data_aprovacao',
    'enviado_por_usuario_id',  // ✅ Novo
    'enviado_em',              // ✅ Novo
];

protected $casts = [
    'data_envio' => 'date',
    'data_aprovacao' => 'datetime',
    'enviado_em' => 'datetime',  // ✅ Novo
];

// ✅ Novo relacionamento
public function enviadoPor(): BelongsTo
{
    return $this->belongsTo(User::class, 'enviado_por_usuario_id');
}
```

---

### 3. ✅ Controller de Anexos Atualizado

**Arquivo:** `app/Http/Controllers/AnexoController.php`

#### index() - Listar anexos

```php
public function index(Request $request, int $solicitacaoId): JsonResponse
{
    // ✅ Eager load do usuário que enviou
    $solicitacao = SolicitacaoPagamento::with([
        'anexos.aprovador',
        'anexos.enviadoPor'  // ✅ Novo
    ])->findOrFail($solicitacaoId);
    
    return response()->json([
        'solicitacao' => [
            // ...
            'anexos' => $solicitacao->anexos->map(function ($anexo) use ($documentoFiscalRecusado) {
                return [
                    'id' => $anexo->id,
                    'tipo_anexo' => $anexo->tipo_anexo,
                    'tipo_anexo_label' => $anexo->tipo_anexo_label,
                    'arquivo_path' => $anexo->arquivo_path ? '/storage/' . $anexo->arquivo_path : null,
                    'arquivo_nome' => $anexo->arquivo_nome,
                    'status' => $anexo->status_label,
                    'data_envio' => $anexo->data_envio?->format('Y-m-d'),
                    'motivo_recusa' => $anexo->motivo_recusa,
                    'is_documento_fiscal' => $isDocumentoFiscal,
                    'pode_reenviar' => $podeReenviar,
                    'pode_remover' => $podeReenviar && $anexo->arquivo_path !== null,
                    
                    // ✅ NOVOS CAMPOS
                    'enviado_por' => $anexo->enviadoPor?->name,
                    'enviado_em' => $anexo->enviado_em?->format('d/m/Y H:i'),
                ];
            }),
        ],
    ]);
}
```

#### upload() - Upload de anexo

```php
public function upload(Request $request, int $solicitacaoId, int $anexoId): JsonResponse
{
    // ... validações ...
    
    // Upload do arquivo
    $file = $request->file('arquivo');
    $path = $file->store('anexos', 'public');
    $nome = $file->getClientOriginalName();
    
    // ✅ Registra quem enviou e quando
    $anexo->update([
        'arquivo_path' => $path,
        'arquivo_nome' => $nome,
        'status' => 'anexo_cadastrado',
        'data_envio' => now()->format('Y-m-d'),
        'motivo_recusa' => null,
        'enviado_por_usuario_id' => $request->user()->id,  // ✅ Novo
        'enviado_em' => now(),                              // ✅ Novo
    ]);
    
    return response()->json([
        'message' => 'Anexo enviado com sucesso',
        'anexo' => [
            'id' => $anexo->id,
            'tipo_anexo' => $anexo->tipo_anexo_label,
            'arquivo_path' => '/storage/' . $anexo->arquivo_path,
            'arquivo_nome' => $anexo->arquivo_nome,
            'status' => $anexo->status_label,
            'data_envio' => $anexo->data_envio->format('Y-m-d'),
        ],
    ]);
}
```

---

### 4. ✅ Controller de Solicitação Atualizado

**Arquivo:** `app/Http/Controllers/SolicitacaoPagamentoController.php`

```php
public function show(Request $request, int $id): JsonResponse
{
    // ✅ Eager load do usuário que enviou
    $solicitacao = SolicitacaoPagamento::with([
        'empenho.contrato.fornecedor',
        'solicitante',
        'anexos.aprovador',
        'anexos.enviadoPor',  // ✅ Novo
        'tramites.usuario',
    ])->findOrFail($id);
    
    return response()->json([
        'solicitacao' => [
            // ... outros campos ...
            
            'anexos' => $solicitacao->anexos->map(function ($anexo) {
                return [
                    'id' => $anexo->id,
                    'tipo_anexo' => $anexo->tipo_anexo,
                    'tipo_anexo_label' => $anexo->tipo_anexo_label,
                    'arquivo_nome' => $anexo->arquivo_nome,
                    'arquivo_path' => $anexo->arquivo_path ? '/storage/' . $anexo->arquivo_path : null,
                    'status' => $anexo->status_label,
                    'data_envio' => $anexo->data_envio?->format('d/m/Y'),
                    'avaliado_por' => $anexo->aprovador?->name,
                    'motivo_recusa' => $anexo->motivo_recusa,
                    
                    // ✅ NOVOS CAMPOS
                    'enviado_por' => $anexo->enviadoPor?->name,
                    'enviado_em' => $anexo->enviado_em?->format('d/m/Y H:i'),
                ];
            }),
        ],
    ]);
}
```

---

### 5. ✅ Seeder Atualizado

**Arquivo:** `database/seeders/POCDemoSeeder.php`

```php
foreach ($tiposAnexo as $tipo => $status) {
    AnexoSolicitacao::create([
        'solicitacao_id' => $solicitacao1->id,
        'tipo_anexo' => $tipo,
        'arquivo_path' => 'anexos/demo/' . $tipo . '.pdf',
        'arquivo_nome' => $tipo . '.pdf',
        'status' => $status,
        'data_envio' => '2024-01-25',
        'enviado_por_usuario_id' => $responsavelTecnico->id,  // ✅ Novo
        'enviado_em' => '2024-01-25 09:30:00',                 // ✅ Novo
    ]);
}
```

---

## 💻 EXEMPLO DE USO NO FRONTEND

```typescript
'use client'

import { useState, useEffect } from 'react'

interface Anexo {
  id: number
  tipo_anexo: string
  tipo_anexo_label: string
  arquivo_nome: string | null
  status: string
  enviado_por: string | null     // ✅ Novo
  enviado_em: string | null       // ✅ Novo
}

export default function AnexosPage({ params }: { params: { id: string } }) {
  const [anexos, setAnexos] = useState<Anexo[]>([])

  const carregarAnexos = async () => {
    const token = localStorage.getItem('token')
    const response = await fetch(
      `http://localhost:3333/api/solicitacoes/${params.id}/anexos`,
      {
        headers: { Authorization: `Bearer ${token}` }
      }
    )
    const data = await response.json()
    setAnexos(data.solicitacao.anexos)
  }

  useEffect(() => {
    carregarAnexos()
  }, [params.id])

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-4">Anexos da Solicitação</h1>

      <div className="space-y-4">
        {anexos.map((anexo) => (
          <div key={anexo.id} className="border rounded-lg p-4 bg-white shadow">
            <div className="flex justify-between items-start">
              <div className="flex-1">
                <h3 className="font-semibold">{anexo.tipo_anexo_label}</h3>
                <p className="text-sm text-gray-500">Status: {anexo.status}</p>
                
                {anexo.arquivo_nome && (
                  <p className="text-sm text-gray-700 mt-1">
                    📄 {anexo.arquivo_nome}
                  </p>
                )}

                {/* ✅ INFORMAÇÕES DE ENVIO */}
                {anexo.enviado_por && anexo.enviado_em && (
                  <div className="mt-3 p-2 bg-blue-50 rounded border border-blue-200">
                    <p className="text-sm text-blue-800">
                      <span className="font-medium">Enviado por:</span> {anexo.enviado_por}
                    </p>
                    <p className="text-sm text-blue-600">
                      <span className="font-medium">Data de envio:</span> {anexo.enviado_em}
                    </p>
                  </div>
                )}

                {/* Mostrar quando ainda não foi enviado */}
                {!anexo.enviado_por && (
                  <div className="mt-3 p-2 bg-gray-50 rounded border border-gray-200">
                    <p className="text-sm text-gray-600">
                      ⏳ Aguardando envio do arquivo
                    </p>
                  </div>
                )}
              </div>

              <div className="flex gap-2">
                {/* Botões de ação... */}
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}
```

---

## 🎨 VARIAÇÕES DE LAYOUT

### Opção 1: Badge Compacto

```tsx
{anexo.enviado_por && (
  <div className="mt-2 flex items-center gap-2 text-xs">
    <span className="px-2 py-1 bg-blue-100 text-blue-700 rounded">
      👤 {anexo.enviado_por}
    </span>
    <span className="px-2 py-1 bg-gray-100 text-gray-700 rounded">
      🕐 {anexo.enviado_em}
    </span>
  </div>
)}
```

### Opção 2: Card com Ícone

```tsx
{anexo.enviado_por && (
  <div className="mt-3 flex items-start gap-3 p-3 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg border border-blue-200">
    <div className="flex-shrink-0 w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
      <span className="text-white text-lg">👤</span>
    </div>
    <div className="flex-1">
      <p className="text-sm font-medium text-blue-900">{anexo.enviado_por}</p>
      <p className="text-xs text-blue-600 mt-1">Enviado em {anexo.enviado_em}</p>
    </div>
  </div>
)}
```

### Opção 3: Timeline Style

```tsx
{anexo.enviado_por && (
  <div className="mt-3 relative pl-8">
    <div className="absolute left-0 top-0 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
      <span className="text-white text-xs">✓</span>
    </div>
    <div className="border-l-2 border-blue-200 pl-4 py-2">
      <p className="text-sm font-medium text-gray-800">
        Enviado por {anexo.enviado_por}
      </p>
      <p className="text-xs text-gray-500">
        {anexo.enviado_em}
      </p>
    </div>
  </div>
)}
```

---

## 📊 FLUXO COMPLETO

1. **Solicitação criada** → Anexos criados com status "pendente"
   - `enviado_por`: null
   - `enviado_em`: null

2. **Responsável faz upload** → Status muda para "anexo_cadastrado"
   - `enviado_por`: "João Silva (Responsável Técnico)"
   - `enviado_em`: "25/01/2024 09:30"

3. **Responsável envia todos para aprovação** → Status muda para "aguardando_aprovacao"
   - Campos mantidos

4. **Gestor aprova** → Status muda para "aprovado"
   - Campos mantidos (mantém histórico de quem enviou)

5. **Gestor recusa** → Status muda para "recusado"
   - Campos mantidos (mantém histórico)

6. **Responsável reenvia** → Status volta para "anexo_cadastrado"
   - `enviado_por`: ATUALIZADO com usuário atual
   - `enviado_em`: ATUALIZADO com data/hora atual

---

## 🚀 CAMPOS DISPONÍVEIS

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `id` | Integer | ✅ Sim | ID do anexo |
| `tipo_anexo` | String | ✅ Sim | Tipo técnico (documento_fiscal, certidao_negativa_debitos, etc) |
| `tipo_anexo_label` | String | ✅ Sim | Nome amigável do tipo |
| `arquivo_path` | String/Null | ❌ Não | Caminho do arquivo no storage |
| `arquivo_nome` | String/Null | ❌ Não | Nome original do arquivo |
| `status` | String | ✅ Sim | Status atual (Pendente, Aguardando Aprovação, etc) |
| `data_envio` | String/Null | ❌ Não | Data do envio (Y-m-d) |
| `motivo_recusa` | String/Null | ❌ Não | Motivo se foi recusado |
| `is_documento_fiscal` | Boolean | ✅ Sim | Flag se é documento fiscal |
| `pode_reenviar` | Boolean | ✅ Sim | Se pode fazer upload/substituir |
| `pode_remover` | Boolean | ✅ Sim | Se pode remover o arquivo |
| `enviado_por` | String/Null | ❌ Não | ✅ **NOVO** - Nome de quem enviou |
| `enviado_em` | String/Null | ❌ Não | ✅ **NOVO** - Data/hora formatada (dd/mm/yyyy HH:mm) |

---

## ✅ CHECKLIST

- [x] Migration criada
- [x] Model atualizado (fillable, casts, relacionamento)
- [x] Controller de anexos atualizado (index, upload)
- [x] Controller de solicitação atualizado (show)
- [x] Seeder atualizado com dados de exemplo
- [x] Banco de dados migrado e testado
- [x] Guia de integração criado

---

**🎉 Backend completo! Os campos `enviado_por` e `enviado_em` já estão disponíveis em todos os endpoints!**
