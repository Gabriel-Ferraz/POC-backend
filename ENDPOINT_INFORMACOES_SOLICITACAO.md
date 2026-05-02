# ✅ ENDPOINT IMPLEMENTADO - Informações da Solicitação

## 📡 GET /api/solicitacoes/{id}

**Status:** ✅ IMPLEMENTADO

**Funcionalidades:**
- ✅ Dados gerais completos (solicitante, fornecedor, contrato, empenho)
- ✅ Documento fiscal completo
- ✅ Forma de pagamento completa
- ✅ **Andamento com 13 etapas** (status: concluido/em_andamento/pendente)
- ✅ Trâmites cronológicos
- ✅ Anexos com status de aprovação
- ✅ Pagamento realizado (quando aplicável)
- ✅ Cancelamento (quando aplicável)

---

## 📊 RESPONSE COMPLETO

```json
{
  "solicitacao": {
    "id": 1,
    "numero": "SP-2024-000001",
    "status": "aguardando_aprovacao_anexos",
    "status_label": "Aguardando Aprovação dos Anexos",
    "valor": "12000.00",
    "created_at": "02/05/2026",
    
    "solicitante": {
      "id": 1,
      "name": "João Silva (Responsável Técnico)",
      "cpf": "12345678900"
    },
    "empenho": {
      "id": 1,
      "numero": "934/2023"
    },
    "fornecedor": {
      "id": 1,
      "cnpj": "12.345.678/0001-90",
      "razao_social": "Fornecedor Demonstração LTDA"
    },
    "contrato": {
      "id": 1,
      "numero": "Contrato 154/2023"
    },
    
    "documento_fiscal_tipo": "Nota Fiscal",
    "documento_fiscal_numero": "12345",
    "documento_fiscal_serie": "001",
    "documento_fiscal_data_emissao": "25/01/2024",
    "documento_fiscal_observacao": "NF-e de serviços",
    
    "forma_pagamento_tipo": "conta_bancaria",
    "banco": "Banco do Brasil",
    "agencia": "1234",
    "agencia_digito": "5",
    "conta": "567890",
    "conta_digito": "1",
    "operacao": "001",
    "cidade_banco": "São José dos Pinhais",
    "observacao_pagamento": null,
    
    "andamento": {
      "etapas": [
        {
          "key": "solicitacao_pagamento",
          "label": "Solicitação de Pagamento",
          "status": "concluido"
        },
        {
          "key": "anexos",
          "label": "Anexos",
          "status": "em_andamento"
        },
        {
          "key": "fiscal",
          "label": "Fiscal",
          "status": "pendente"
        },
        {
          "key": "gestor",
          "label": "Gestor",
          "status": "pendente"
        },
        {
          "key": "liquidacao",
          "label": "Liquidação",
          "status": "pendente"
        },
        {
          "key": "secretario",
          "label": "Secretário(a)",
          "status": "pendente"
        },
        {
          "key": "iss",
          "label": "ISS",
          "status": "pendente"
        },
        {
          "key": "ordem_pagamento",
          "label": "Ordem de Pagamento",
          "status": "pendente"
        },
        {
          "key": "autorizacao",
          "label": "Autorização",
          "status": "pendente"
        },
        {
          "key": "bordero",
          "label": "Borderô",
          "status": "pendente"
        },
        {
          "key": "remessa",
          "label": "Remessa",
          "status": "pendente"
        },
        {
          "key": "pagamento",
          "label": "Pagamento",
          "status": "pendente"
        },
        {
          "key": "pagamento_realizado",
          "label": "Pagamento Realizado",
          "status": "pendente"
        }
      ]
    },
    
    "tramites": [
      {
        "id": 1,
        "fase": "Solicitação Criada",
        "created_at": "02/05/2026 09:17",
        "usuario": {
          "id": 1,
          "name": "João Silva (Responsável Técnico)"
        },
        "observacao": "Solicitação de pagamento criada pelo fornecedor",
        "motivo": null
      },
      {
        "id": 2,
        "fase": "Anexos Enviados para Aprovação",
        "created_at": "02/05/2026 09:17",
        "usuario": {
          "id": 1,
          "name": "João Silva (Responsável Técnico)"
        },
        "observacao": "Todos os anexos foram enviados e aguardam aprovação do Gestor do Contrato",
        "motivo": null
      }
    ],
    
    "anexos": [
      {
        "id": 1,
        "tipo_anexo": "documento_fiscal",
        "tipo_anexo_label": "Documento Fiscal (NF, Recibo, Guias, Faturas, etc.)",
        "arquivo_nome": "documento_fiscal.pdf",
        "arquivo_path": "/storage/anexos/demo/documento_fiscal.pdf",
        "status": "Recusado",
        "data_envio": "25/01/2024",
        "avaliado_por": null,
        "motivo_recusa": "Nota fiscal com data incorreta"
      },
      {
        "id": 2,
        "tipo_anexo": "certidao_negativa_debitos",
        "tipo_anexo_label": "Certidão Negativa de Débitos",
        "arquivo_nome": "certidao_negativa_debitos.pdf",
        "arquivo_path": "/storage/anexos/demo/certidao_negativa_debitos.pdf",
        "status": "Aguardando Aprovação",
        "data_envio": "25/01/2024",
        "avaliado_por": null,
        "motivo_recusa": null
      }
    ],
    
    "pagamento_realizado": null,
    
    "cancelamento": null
  }
}
```

---

## 🎯 MAPEAMENTO DE STATUS → ANDAMENTO

| Status da Solicitação | Etapas Concluídas | Etapa em Andamento | Etapas Pendentes |
|----------------------|-------------------|-------------------|------------------|
| `pendente` | Nenhuma | Solicitação Pagamento | Todas as outras |
| `aguardando_aprovacao_anexos` | Solicitação Pagamento | Anexos | Fiscal em diante |
| `anexos_recusados` | Solicitação Pagamento | Anexos | Fiscal em diante |
| `aguardando_autorizacao_gestor` | Solicitação, Anexos, Fiscal | Gestor | Liquidação em diante |
| `em_liquidacao` | Até Gestor | Liquidação | Secretário em diante |
| `em_ordem_pagamento` | Até ISS | Ordem Pagamento | Autorização em diante |
| `pagamento_em_remessa` | Até Remessa | Pagamento | Pagamento Realizado |
| `pagamento_realizado` | **TODAS** | Nenhuma | Nenhuma |

---

## 📋 12 ABAS - DADOS DISPONÍVEIS

### 1. ABA: GERAL
```json
{
  "numero": "SP-2024-000001",
  "created_at": "02/05/2026",
  "valor": "12000.00",
  "solicitante": {...},
  "empenho": {...},
  "fornecedor": {...},
  "contrato": {...},
  "documento_fiscal_tipo": "Nota Fiscal",
  "documento_fiscal_numero": "12345",
  "documento_fiscal_serie": "001",
  "documento_fiscal_data_emissao": "25/01/2024"
}
```

### 2. ABA: TRÂMITES
```json
"tramites": [
  {
    "fase": "Solicitação Criada",
    "created_at": "02/05/2026 09:17",
    "usuario": {...},
    "observacao": "..."
  }
]
```

### 3. ABA: ANEXOS PAGAMENTO
```json
"anexos": [
  {
    "tipo_anexo_label": "Documento Fiscal",
    "data_envio": "25/01/2024",
    "arquivo_nome": "documento_fiscal.pdf",
    "status": "Aguardando Aprovação",
    "avaliado_por": "Maria Santos"
  }
]
```

### 4-8. ABAS: GESTOR, LIQUIDAÇÃO, PROCESSO, ORDEM PAGAMENTO, ISS
Usar campo `tramites` filtrado por fase

### 9. ABA: FORMA DE PAGAMENTO
```json
{
  "forma_pagamento_tipo": "conta_bancaria",
  "banco": "Banco do Brasil",
  "agencia": "1234",
  "agencia_digito": "5",
  "conta": "567890",
  "conta_digito": "1",
  "operacao": "001",
  "cidade_banco": "São José dos Pinhais"
}
```

### 10-11. ABAS: BORDERÔ, REMESSA
Usar campo `tramites` filtrado por fase

### 12. ABA: PAGAMENTO REALIZADO
```json
"pagamento_realizado": {
  "data_hora": "20/01/2023 14:30",
  "valor": "12000.00"
}
```

---

## 🎨 COMO USAR NO FRONTEND

### Exibir Andamento Visual

```typescript
const renderAndamento = () => {
  return solicitacao.andamento.etapas.map((etapa) => {
    const color = {
      'concluido': 'bg-green-500',
      'em_andamento': 'bg-orange-500',
      'pendente': 'bg-gray-300'
    }[etapa.status]

    return (
      <div key={etapa.key} className={`${color} p-2 rounded`}>
        {etapa.label}
      </div>
    )
  })
}
```

### Exibir Trâmites

```typescript
const renderTramites = () => {
  return solicitacao.tramites.map((tramite) => (
    <div key={tramite.id} className="border-b py-2">
      <p className="font-bold">{tramite.fase}</p>
      <p className="text-sm text-gray-600">
        {tramite.created_at} - {tramite.usuario?.name}
      </p>
      {tramite.observacao && (
        <p className="text-sm">{tramite.observacao}</p>
      )}
      {tramite.motivo && (
        <p className="text-sm text-red-600">Motivo: {tramite.motivo}</p>
      )}
    </div>
  ))
}
```

---

## ✅ CHECKLIST

- [x] Criar método `getAndamentoAttribute()` no model
- [x] Mapear todos os status para etapas
- [x] Atualizar endpoint `show` com response completo
- [x] Incluir dados do solicitante, fornecedor, contrato
- [x] Incluir documento fiscal completo
- [x] Incluir forma de pagamento completa
- [x] Incluir andamento com 13 etapas
- [x] Incluir trâmites com usuário
- [x] Incluir anexos com avaliador
- [x] Incluir pagamento_realizado (quando aplicável)
- [x] Testar endpoint

---

**🎉 Backend completo e testado! Pronto para o frontend consumir!**
