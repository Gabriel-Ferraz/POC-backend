# 🔄 FLUXO COMPLETO - APROVAÇÃO DE ANEXOS

## 📋 Visão Geral

Após o envio de todos os anexos da solicitação de pagamento, a solicitação será encaminhada automaticamente para o **Gestor do Contrato** que procederá com a **Aprovação dos Anexos**.

---

## 👥 PERSONAS

### 1️⃣ Responsável Técnico (João Silva)
- **Perfil:** `responsavel_tecnico`
- **CPF:** 12345678900
- **Senha:** senha123
- **Ações:**
  - Criar solicitação de pagamento
  - Fazer upload dos 5 anexos obrigatórios
  - Enviar todos para aprovação
  - Corrigir anexos recusados (exceto Documento Fiscal)
  - Cancelar solicitação (se Documento Fiscal for recusado)

### 2️⃣ Gestor do Contrato (Maria Santos)
- **Perfil:** `gestor_contrato`
- **CPF:** 98765432100
- **Senha:** senha123
- **Ações:**
  - Visualizar solicitações pendentes de aprovação
  - Aprovar anexos
  - Recusar anexos (com motivo obrigatório)

---

## 🔄 FLUXO DETALHADO

### 📤 ETAPA 1: Envio dos Anexos (Responsável Técnico)

1. Responsável Técnico cria nova solicitação de pagamento
   - Backend cria automaticamente 5 anexos com status `pendente`

2. Faz upload de cada anexo (PDF, máx 10MB)
   - Status do anexo muda para `anexo_cadastrado`
   - Campo `data_envio` é preenchido

3. Quando todos os 5 anexos tiverem arquivo, clica em **"Enviar Todos para Aprovação"**
   - Status de todos os anexos muda para `aguardando_aprovacao`
   - Status da solicitação muda para `aguardando_aprovacao_anexos`
   - Trâmite é registrado
   - **Solicitação é encaminhada automaticamente para o Gestor do Contrato**

---

### ✅ ETAPA 2A: Aprovação (Gestor do Contrato)

**Endpoint:** `GET /api/gestor/solicitacoes-pendentes`

**Response:**
```json
{
  "solicitacoes": [
    {
      "id": 1,
      "numero": "SP-2024-000001",
      "valor": 12000.00,
      "status": "aguardando_aprovacao_anexos",
      "data": "02/05/2024 10:30",
      "solicitante": "João Silva",
      "fornecedor": "Fornecedor Demonstração LTDA",
      "empenho": "934/2023",
      "contrato": "Contrato 154/2023",
      "documento_fiscal": "Nota Fiscal 12345",
      "total_anexos": 5,
      "anexos_aprovados": 0,
      "anexos_pendentes": 5,
      "anexos_recusados": 0
    }
  ]
}
```

**Gestor clica na solicitação para ver detalhes:**

`GET /api/gestor/solicitacoes/{id}`

**Gestor aprova cada anexo:**

`POST /api/anexos/{anexoId}/aprovar`

**Quando todos os 5 anexos forem aprovados:**
- Status de todos os anexos muda para `aprovado`
- Status da solicitação muda para `aguardando_autorizacao_gestor`
- Trâmite é registrado: "Todos os anexos foram aprovados. Solicitação prossegue no fluxo interno da PMSJP"
- **✅ Solicitação percorre todo o fluxo interno nos departamentos da PMSJP até a efetivação do pagamento**

**Responsável Técnico pode acompanhar em tempo real:**
- `GET /api/solicitacoes/{id}/tramites`

---

### ❌ ETAPA 2B: Recusa de Anexo (Gestor do Contrato)

**Gestor recusa um anexo:**

`POST /api/anexos/{anexoId}/recusar`

```json
{
  "motivo": "Certidão vencida. Por favor, envie uma certidão atualizada."
}
```

**O que acontece:**
- Status do anexo muda para `recusado`
- Campo `motivo_recusa` é preenchido
- Status da solicitação muda para `anexos_recusados`
- Trâmite é registrado com mensagem específica

---

### 🔁 ETAPA 3A: Correção de Anexo NÃO-FISCAL (Responsável Técnico)

**Se o anexo recusado NÃO for Documento Fiscal:**

1. Responsável Técnico vê o motivo da recusa
   - `GET /api/solicitacoes/{id}`
   - No anexo recusado aparece o `motivo_recusa`

2. Faz upload novamente do anexo correto
   - `POST /api/solicitacoes/{solicitacaoId}/anexos/{anexoId}/upload`
   - Status do anexo muda de `recusado` para `anexo_cadastrado`
   - Campo `motivo_recusa` é limpo

3. Clica em "Enviar Todos para Aprovação" novamente
   - `POST /api/solicitacoes/{solicitacaoId}/anexos/enviar-todos`
   - Status dos anexos corrigidos muda para `aguardando_aprovacao`
   - Status da solicitação muda para `aguardando_aprovacao_anexos`
   - Trâmite registrado: "Anexos corrigidos e reenviados para aprovação do Gestor do Contrato"
   - **🔄 Volta para a aprovação do Gestor**

---

### 🚫 ETAPA 3B: Recusa de DOCUMENTO FISCAL (Responsável Técnico)

**Se o Documento Fiscal/Recibo for recusado:**

**Mensagem especial no trâmite:**
> "Anexo Documento Fiscal (NF, Recibo, Guias, Faturas, etc.) foi recusado. ATENÇÃO: Documento Fiscal recusado não pode ser corrigido. Você deve cancelar esta solicitação e criar uma nova com o documento correto."

**O que o Responsável Técnico DEVE fazer:**

1. **CANCELAR a solicitação:**
   - `POST /api/solicitacoes/{id}/cancelar`
   
   ```json
   {
     "data_cancelamento": "2024-05-02",
     "motivo": "Documento Fiscal recusado pelo Gestor. Criando nova solicitação com documento correto."
   }
   ```

2. **CRIAR uma nova solicitação de pagamento:**
   - `POST /api/empenhos/{empenhoId}/solicitacoes`
   - Com o Documento Fiscal correto

3. **Fazer upload dos anexos novamente na nova solicitação**

**⚠️ IMPORTANTE:** 
- O sistema **NÃO permite** substituir o Documento Fiscal recusado
- A única opção é cancelar e criar nova solicitação

---

## 📊 DIAGRAMA DO FLUXO

```
┌─────────────────────────────────────────────────────────────┐
│ RESPONSÁVEL TÉCNICO                                         │
│ 1. Criar solicitação                                        │
│ 2. Upload de 5 anexos                                       │
│ 3. Enviar todos para aprovação                              │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ SISTEMA                                                      │
│ - Status solicitação: aguardando_aprovacao_anexos           │
│ - Status anexos: aguardando_aprovacao                       │
│ - Encaminha para Gestor do Contrato                         │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ GESTOR DO CONTRATO                                          │
│ - Lista solicitações pendentes                              │
│ - Visualiza cada anexo                                      │
│ - Aprova ou Recusa cada anexo                               │
└────────────────┬────────────────────────────────────────────┘
                 │
         ┌───────┴───────┐
         │               │
         ▼               ▼
    ✅ TODOS         ❌ ALGUM
    APROVADOS        RECUSADO
         │               │
         │               ▼
         │          ┌─────────────────────┐
         │          │ Documento Fiscal?   │
         │          └──────┬──────────────┘
         │                 │
         │         ┌───────┴───────┐
         │         │               │
         │         ▼               ▼
         │      ❌ SIM         ✅ NÃO
         │         │               │
         │         │               ▼
         │         │          Corrigir e
         │         │          Reenviar
         │         │               │
         │         │               └──────┐
         │         ▼                      │
         │    Cancelar                    │
         │    Solicitação                 │
         │         │                      │
         │         ▼                      │
         │    Criar Nova                  │
         │    Solicitação                 │
         │                                │
         │◄───────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────┐
│ SISTEMA                                                      │
│ - Status solicitação: aguardando_autorizacao_gestor         │
│ - Status anexos: aprovado                                   │
│ - Prossegue no fluxo interno da PMSJP                       │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ FLUXO INTERNO PMSJP                                         │
│ - Em Liquidação                                             │
│ - Em Ordem de Pagamento                                     │
│ - Pagamento em Remessa                                      │
│ - Pagamento Realizado                                       │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 STATUS DA SOLICITAÇÃO

| Status | Descrição |
|--------|-----------|
| `pendente` | Criada, aguardando envio dos anexos |
| `aguardando_aprovacao_anexos` | Anexos enviados, aguardando aprovação do Gestor |
| `anexos_recusados` | Algum anexo foi recusado pelo Gestor |
| `aguardando_autorizacao_gestor` | Todos anexos aprovados, aguardando próxima etapa |
| `em_liquidacao` | Em liquidação (fluxo interno PMSJP) |
| `em_ordem_pagamento` | Em ordem de pagamento (fluxo interno PMSJP) |
| `pagamento_em_remessa` | Pagamento em remessa bancária (fluxo interno PMSJP) |
| `pagamento_realizado` | Pagamento efetivado ✅ |
| `cancelada` | Solicitação cancelada |

---

## 🎯 STATUS DOS ANEXOS

| Status | Descrição |
|--------|-----------|
| `pendente` | Anexo criado, aguardando upload |
| `anexo_cadastrado` | Upload feito, não enviado para aprovação |
| `aguardando_aprovacao` | Enviado para aprovação do Gestor |
| `aprovado` | Aprovado pelo Gestor ✅ |
| `recusado` | Recusado pelo Gestor ❌ |

---

## 📡 ENDPOINTS IMPLEMENTADOS

### Para o Responsável Técnico:

1. **Listar anexos da solicitação**
   ```
   GET /api/solicitacoes/{solicitacaoId}/anexos
   ```

2. **Upload de anexo**
   ```
   POST /api/solicitacoes/{solicitacaoId}/anexos/{anexoId}/upload
   Body (FormData): arquivo (PDF, max 10MB)
   ```

3. **Remover anexo**
   ```
   DELETE /api/solicitacoes/{solicitacaoId}/anexos/{anexoId}
   ```

4. **Enviar todos para aprovação**
   ```
   POST /api/solicitacoes/{solicitacaoId}/anexos/enviar-todos
   ```

5. **Cancelar solicitação**
   ```
   POST /api/solicitacoes/{id}/cancelar
   Body: { data_cancelamento, motivo }
   ```

6. **Visualizar trâmites (acompanhamento em tempo real)**
   ```
   GET /api/solicitacoes/{id}/tramites
   ```

### Para o Gestor do Contrato:

1. **Listar solicitações pendentes de aprovação**
   ```
   GET /api/gestor/solicitacoes-pendentes
   ```

2. **Ver detalhes da solicitação (com anexos)**
   ```
   GET /api/gestor/solicitacoes/{id}
   ```

3. **Aprovar anexo**
   ```
   POST /api/anexos/{anexoId}/aprovar
   ```

4. **Recusar anexo**
   ```
   POST /api/anexos/{anexoId}/recusar
   Body: { motivo }
   ```

5. **Download do anexo**
   ```
   GET /api/solicitacoes/{solicitacaoId}/anexos/{anexoId}/download
   ```

---

## 🔐 PERMISSÕES

| Ação | Responsável Técnico | Gestor do Contrato |
|------|---------------------|-------------------|
| Criar solicitação | ✅ | ❌ |
| Upload de anexos | ✅ (própria solicitação) | ❌ |
| Remover anexos | ✅ (se não aprovado) | ❌ |
| Enviar para aprovação | ✅ | ❌ |
| Listar pendentes aprovação | ❌ | ✅ |
| Aprovar anexos | ❌ | ✅ |
| Recusar anexos | ❌ | ✅ |
| Cancelar solicitação | ✅ (própria solicitação) | ❌ |
| Ver trâmites | ✅ | ✅ |

---

## ✅ IMPLEMENTAÇÃO COMPLETA

🎉 **Backend totalmente implementado com:**
- ✅ Fluxo automático de aprovação
- ✅ Regra especial para Documento Fiscal recusado
- ✅ Reenvio de anexos corrigidos
- ✅ Registro de trâmites em tempo real
- ✅ Validações de permissão
- ✅ Mensagens contextuais
- ✅ Acompanhamento em tempo real pelo Responsável Técnico

**🚀 Pronto para integração com o frontend!**
