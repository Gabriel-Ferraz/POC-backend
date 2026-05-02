# ✅ CORRIGIDO: Trâmites com Origem/Destino/Motivo

## 🎯 CORREÇÃO REALIZADA

O backend foi **corrigido** para usar as **ETAPAS DO PROCESSO** em vez de cargos/pessoas.

**Antes (❌ ERRADO):**
```
Origem: "Responsável Técnico" → Destino: "Gestor do Contrato"
```

**Depois (✅ CORRETO):**
```
Origem: "Anexar Documentos" → Destino: "Fiscal"
```

---

## 📊 ETAPAS DO PROCESSO (Conforme Imagem)

As etapas corretas que devem ser usadas:

| Etapa | Descrição |
|-------|-----------|
| `Solicitação de Pagamento` | Criação da solicitação |
| `Anexar Documentos` | Upload e aprovação de anexos |
| `Fiscal` | Análise pelo fiscal |
| `Gestor` | Aprovação do gestor |
| `Comissão de Liquidação` | Liquidação da despesa |
| `Secretário` | Aprovação do secretário |
| `ISS` | Verificação de ISS |
| `Ordem de Pagamento` | Emissão da ordem |
| `Borderô` | Inclusão no borderô |
| `Remessa` | Envio para remessa bancária |
| `Pagamento` | Processamento |
| `Pagamento Realizado` | Pagamento efetivado |
| `Cancelada` | Cancelamento |

---

## 📡 RESPONSE ATUALIZADO

### GET /api/solicitacoes/{id}

```json
{
  "solicitacao": {
    "id": 2,
    "numero": "SP-2023-000125",
    "status": "pagamento_realizado",
    
    "tramites": [
      {
        "id": 4,
        "fase": "Solicitação de Pagamento",
        "created_at": "02/05/2026 12:00",
        "usuario": {
          "id": 1,
          "name": "João Silva (Responsável Técnico)"
        },
        "origem": null,
        "destino": "Anexar Documentos",
        "motivo": null,
        "observacao": "Solicitação de pagamento criada"
      },
      {
        "id": 5,
        "fase": "Anexos Aprovados",
        "created_at": "02/05/2026 12:15",
        "usuario": {
          "id": 2,
          "name": "Maria Santos (Gestor Contrato)"
        },
        "origem": "Fiscal",
        "destino": "Gestor",
        "motivo": null,
        "observacao": "Todos os anexos foram aprovados"
      },
      {
        "id": 6,
        "fase": "Gestor",
        "created_at": "02/05/2026 13:00",
        "usuario": {
          "id": 2,
          "name": "Maria Santos (Gestor Contrato)"
        },
        "origem": "Gestor",
        "destino": "Comissão de Liquidação",
        "motivo": null,
        "observacao": "Aprovado pelo gestor do contrato"
      },
      {
        "id": 7,
        "fase": "Comissão de Liquidação",
        "created_at": "02/05/2026 13:35",
        "usuario": {
          "id": 3,
          "name": "Carlos Oliveira (Operador PMSJP)"
        },
        "origem": "Comissão de Liquidação",
        "destino": "Secretário",
        "motivo": null,
        "observacao": "Liquidação aprovada"
      },
      {
        "id": 8,
        "fase": "Secretário",
        "created_at": "02/05/2026 14:25",
        "usuario": {
          "id": 3,
          "name": "Carlos Oliveira (Operador PMSJP)"
        },
        "origem": "Secretário",
        "destino": "ISS",
        "motivo": null,
        "observacao": "Secretário aprovou"
      },
      {
        "id": 9,
        "fase": "ISS",
        "created_at": "02/05/2026 14:03",
        "usuario": {
          "id": 3,
          "name": "Carlos Oliveira (Operador PMSJP)"
        },
        "origem": "ISS",
        "destino": "Ordem de Pagamento",
        "motivo": null,
        "observacao": "ISS emitido"
      },
      {
        "id": 10,
        "fase": "Ordem de Pagamento",
        "created_at": "02/05/2026 14:09",
        "usuario": {
          "id": 3,
          "name": "Carlos Oliveira (Operador PMSJP)"
        },
        "origem": "Ordem de Pagamento",
        "destino": "Borderô",
        "motivo": null,
        "observacao": "Ordem de pagamento emitida"
      },
      {
        "id": 11,
        "fase": "Borderô",
        "created_at": "02/05/2026 15:01",
        "usuario": {
          "id": 3,
          "name": "Carlos Oliveira (Operador PMSJP)"
        },
        "origem": "Borderô",
        "destino": "Remessa",
        "motivo": null,
        "observacao": "Borderô cadastrado"
      },
      {
        "id": 12,
        "fase": "Remessa",
        "created_at": "02/05/2026 14:16",
        "usuario": {
          "id": 3,
          "name": "Carlos Oliveira (Operador PMSJP)"
        },
        "origem": "Remessa",
        "destino": "Pagamento",
        "motivo": null,
        "observacao": "Remessa cadastrada"
      },
      {
        "id": 13,
        "fase": "Pagamento",
        "created_at": "02/05/2026 08:28",
        "usuario": {
          "id": 3,
          "name": "Carlos Oliveira (Operador PMSJP)"
        },
        "origem": "Pagamento",
        "destino": "Pagamento Realizado",
        "motivo": "Pagamento Realizado Bco: 406 Ag: 406 Conta: 123-4",
        "observacao": "Pagamento realizado via transferência bancária"
      }
    ]
  }
}
```

---

## 🔧 IMPLEMENTAÇÕES CORRIGIDAS

### 1. Criação da Solicitação

**Arquivo:** `app/Http/Controllers/SolicitacaoPagamentoController.php`

```php
$solicitacao->registrarTramite(
    'Solicitação de Pagamento',
    $request->user()->id,
    null,                      // ✅ origem: null (início do processo)
    'Anexar Documentos',       // ✅ destino: próxima etapa
    'Solicitação de pagamento criada pelo fornecedor'
);
```

---

### 2. Envio de Anexos para Aprovação

**Arquivo:** `app/Http/Controllers/AnexoController.php`

```php
$solicitacao->registrarTramite(
    'Anexos Enviados para Aprovação',
    $request->user()->id,
    'Anexar Documentos',       // ✅ origem: etapa atual
    'Fiscal',                  // ✅ destino: próxima etapa
    $mensagem
);
```

---

### 3. Todos Anexos Aprovados

**Arquivo:** `app/Http/Controllers/AnexoController.php`

```php
$anexo->solicitacao->registrarTramite(
    'Todos Anexos Aprovados',
    $request->user()->id,
    'Fiscal',                  // ✅ origem: quem aprovou
    'Gestor',                  // ✅ destino: próxima etapa
    'Todos os anexos foram aprovados. Solicitação prossegue no fluxo interno da PMSJP'
);
```

---

### 4. Anexo Recusado

**Arquivo:** `app/Http/Controllers/AnexoController.php`

```php
$anexo->solicitacao->registrarTramite(
    'Anexo Recusado',
    $request->user()->id,
    'Fiscal',                  // ✅ origem: quem recusou
    'Anexar Documentos',       // ✅ destino: volta para correção
    $mensagem,
    $request->motivo           // ✅ motivo da recusa
);
```

---

### 5. Cancelamento

**Arquivo:** `app/Http/Controllers/SolicitacaoPagamentoController.php`

```php
$solicitacao->registrarTramite(
    'Cancelamento',
    $request->user()->id,
    $solicitacao->status,      // ✅ origem: status atual
    'Cancelada',               // ✅ destino: cancelada
    'Solicitação cancelada pelo responsável técnico',
    $request->motivo           // ✅ motivo do cancelamento
);
```

---

## 📝 FLUXO COMPLETO EXEMPLO (Seeder)

**Arquivo:** `database/seeders/POCDemoSeeder.php`

```php
// 1. Criação
$solicitacao->registrarTramite(
    'Solicitação de Pagamento',
    $responsavelTecnico->id,
    null,
    'Anexar Documentos',
    'Solicitação de pagamento criada'
);

// 2. Anexos aprovados
$solicitacao->registrarTramite(
    'Anexos Aprovados',
    $gestorContrato->id,
    'Fiscal',
    'Gestor',
    'Todos os anexos foram aprovados'
);

// 3. Gestor aprova
$solicitacao->registrarTramite(
    'Gestor',
    $gestorContrato->id,
    'Gestor',
    'Comissão de Liquidação',
    'Aprovado pelo gestor do contrato'
);

// 4. Liquidação
$solicitacao->registrarTramite(
    'Comissão de Liquidação',
    $operadorPmsjp->id,
    'Comissão de Liquidação',
    'Secretário',
    'Liquidação aprovada'
);

// 5. Secretário
$solicitacao->registrarTramite(
    'Secretário',
    $operadorPmsjp->id,
    'Secretário',
    'ISS',
    'Secretário aprovou'
);

// 6. ISS
$solicitacao->registrarTramite(
    'ISS',
    $operadorPmsjp->id,
    'ISS',
    'Ordem de Pagamento',
    'ISS emitido'
);

// 7. Ordem de Pagamento
$solicitacao->registrarTramite(
    'Ordem de Pagamento',
    $operadorPmsjp->id,
    'Ordem de Pagamento',
    'Borderô',
    'Ordem de pagamento emitida'
);

// 8. Borderô
$solicitacao->registrarTramite(
    'Borderô',
    $operadorPmsjp->id,
    'Borderô',
    'Remessa',
    'Borderô cadastrado'
);

// 9. Remessa
$solicitacao->registrarTramite(
    'Remessa',
    $operadorPmsjp->id,
    'Remessa',
    'Pagamento',
    'Remessa cadastrada'
);

// 10. Pagamento Realizado
$solicitacao->registrarTramite(
    'Pagamento',
    $operadorPmsjp->id,
    'Pagamento',
    'Pagamento Realizado',
    'Pagamento realizado via transferência bancária',
    'Pagamento Realizado Bco: 406 Ag: 406 Conta: 123-4'  // Motivo com detalhes bancários
);
```

---

## ✅ REGRAS ATUALIZADAS

### Origem
- **Primeira tramitação:** `null` (não tem origem)
- **Demais tramitações:** Etapa de onde SAIU

### Destino
- **Sempre:** Etapa para onde VAI
- **Cancelamento:** "Cancelada"
- **Pagamento final:** "Pagamento Realizado"

### Motivo
- **Uso obrigatório em:**
  - ❌ Recusas (motivo da recusa)
  - ❌ Cancelamentos (motivo do cancelamento)
  - ✅ Pagamento realizado (dados bancários)
- **NÃO usar em aprovações normais do fluxo**

### Observacao
- **Sempre:** Descrição do que aconteceu
- Pode incluir números de documentos, nomes de anexos, etc.

---

## 🎨 EXEMPLO NO FRONTEND

```typescript
{tramites.map((tramite) => (
  <div key={tramite.id} className="border rounded-lg p-4">
    {/* Fase e Data */}
    <div className="flex justify-between">
      <h3 className="font-bold">{tramite.fase}</h3>
      <span className="text-sm text-gray-500">{tramite.created_at}</span>
    </div>
    
    {/* Usuário */}
    {tramite.usuario && (
      <p className="text-sm text-gray-600 mt-2">
        👤 {tramite.usuario.name}
      </p>
    )}
    
    {/* Fluxo: Origem → Destino */}
    {(tramite.origem || tramite.destino) && (
      <div className="mt-3 flex items-center gap-2 p-2 bg-blue-50 rounded">
        {tramite.origem && (
          <span className="px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm font-medium">
            {tramite.origem}
          </span>
        )}
        {tramite.origem && tramite.destino && (
          <span className="text-blue-400 text-lg">→</span>
        )}
        {tramite.destino && (
          <span className="px-3 py-1 bg-green-100 text-green-700 rounded text-sm font-medium">
            {tramite.destino}
          </span>
        )}
      </div>
    )}
    
    {/* Observação */}
    {tramite.observacao && (
      <p className="text-sm text-gray-700 mt-3">
        📝 {tramite.observacao}
      </p>
    )}
    
    {/* Motivo (destaque) */}
    {tramite.motivo && (
      <div className="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
        <p className="text-sm font-semibold text-yellow-800">
          ℹ️ Motivo:
        </p>
        <p className="text-sm text-yellow-700 mt-1">
          {tramite.motivo}
        </p>
      </div>
    )}
  </div>
))}
```

---

## 📊 COMPARAÇÃO

| Item | Antes (❌) | Depois (✅) |
|------|-----------|------------|
| Origem | "Responsável Técnico" | "Anexar Documentos" |
| Destino | "Gestor do Contrato" | "Fiscal" |
| Tipo | Cargo/Pessoa | Etapa do Processo |
| Usuário | No campo origem/destino | No campo `usuario` |

---

## ✅ CHECKLIST

- [x] Corrigir chamadas em SolicitacaoPagamentoController
- [x] Corrigir chamadas em AnexoController
- [x] Corrigir seeder com dados de exemplo
- [x] Testar migração e seeding
- [x] Criar guia atualizado

---

**🎉 Backend corrigido e alinhado com a imagem do fluxo!**
