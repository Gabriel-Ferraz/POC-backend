# 📋 ESTRUTURA DA TELA: NOVA SOLICITAÇÃO DE PAGAMENTO

## 🎯 4 Blocos Visuais

---

## 📦 BLOCO 1: VALOR

### Campos:
- **valor** (obrigatório) - `number` - Valor da solicitação de pagamento

---

## 📄 BLOCO 2: INFORMAÇÕES DO DOCUMENTO FISCAL/RECIBO

### Campos:
- **tipo_documento** (obrigatório) - `string` - Ex: "Nota Fiscal", "Recibo", etc
- **numero_documento** (obrigatório) - `string` - Ex: "12345"
- **serie** (opcional) - `string` - Ex: "001", "A"
- **data_emissao_documento** (obrigatório) - `date` - Formato: YYYY-MM-DD
- **observacao_documento** (opcional) ⭐ **NOVO** - `string` - Observações sobre o documento

---

## 💳 BLOCO 3: INFORMAÇÕES DE PAGAMENTO

### Campo Principal:
- **forma_pagamento** (obrigatório) - `enum: ['conta_bancaria', 'documento']`

### Opções:

#### 1️⃣ Quando selecionar: `conta_bancaria`
**Campos obrigatórios:**
- **banco** - `string` - Nome do banco
- **agencia** - `string` - Número da agência
- **digito_agencia** (opcional) - `string` - Dígito verificador da agência
- **conta** - `string` - Número da conta
- **digito_conta** (opcional) - `string` - Dígito verificador da conta

**Campos opcionais:**
- **operacao** - `string` - Ex: "013" (para poupança)
- **cidade_banco** - `string` - Cidade do banco

#### 2️⃣ Quando selecionar: `documento`
Nenhum campo bancário é obrigatório (todos nullable).

---

## 💬 BLOCO 4: OBSERVAÇÃO DO PAGAMENTO

### Campo:
- **observacao_pagamento** (opcional) ⭐ **NOVO** - `string` - Observações gerais sobre o pagamento

---

## 📡 ENDPOINT DE CRIAÇÃO

```http
POST /api/empenhos/{empenhoId}/solicitacoes
Authorization: Bearer {token}
Content-Type: application/json
```

### Request Body:

```json
{
  // BLOCO 1: Valor
  "valor": 12000.00,

  // BLOCO 2: Documento Fiscal
  "tipo_documento": "Nota Fiscal",
  "numero_documento": "12345",
  "serie": "001",
  "data_emissao_documento": "2024-01-25",
  "observacao_documento": "Documento referente ao serviço X",

  // BLOCO 3: Forma de Pagamento
  "forma_pagamento": "conta_bancaria",
  "banco": "Banco do Brasil",
  "agencia": "1234",
  "digito_agencia": "5",
  "conta": "56789",
  "digito_conta": "0",
  "operacao": "013",
  "cidade_banco": "São José dos Pinhais",

  // BLOCO 4: Observação do Pagamento
  "observacao_pagamento": "Pagamento referente ao contrato X do mês Y"
}
```

### Response (Sucesso - 201):

```json
{
  "message": "Solicitação criada com sucesso",
  "solicitacao": {
    "id": 1,
    "numero": "SP-2024-000001",
    "valor": 12000.00,
    "status": "pendente"
  }
}
```

### Response (Erro - 422):

```json
{
  "message": "Dados inválidos",
  "errors": {
    "valor": ["O campo valor é obrigatório."],
    "tipo_documento": ["O campo tipo documento é obrigatório."],
    "banco": ["O campo banco é obrigatório quando forma pagamento é conta bancaria."]
  }
}
```

---

## ✅ VALIDAÇÕES

### Campos sempre obrigatórios:
- `valor`
- `tipo_documento`
- `numero_documento`
- `data_emissao_documento`
- `forma_pagamento`

### Campos obrigatórios apenas quando `forma_pagamento = "conta_bancaria"`:
- `banco`
- `agencia`
- `conta`

### Campos opcionais (nullable):
- `serie`
- `observacao_documento` ⭐ **NOVO**
- `digito_agencia`
- `digito_conta`
- `operacao`
- `cidade_banco`
- `observacao_pagamento` ⭐ **NOVO**

---

## 🔄 REGRAS DE NEGÓCIO

1. **Saldo do empenho:** O valor da solicitação não pode exceder o saldo disponível do empenho
2. **Status inicial:** Toda solicitação começa com status `pendente`
3. **Anexos automáticos:** Ao criar a solicitação, o sistema cria automaticamente 5 anexos obrigatórios com status `pendente`:
   - `documento_fiscal`
   - `certidao_negativa_debitos`
   - `certidao_tributaria`
   - `guia_previdencia_social`
   - `fgts`
4. **Trâmite inicial:** Registra automaticamente o trâmite "Solicitação Criada"
5. **Bloqueio de saldo:** O valor da solicitação é bloqueado do saldo do empenho

---

## 🎨 SUGESTÃO DE LAYOUT NO FRONTEND

```
┌─────────────────────────────────────────────────┐
│ BLOCO 1: VALOR                                  │
│ ┌─────────────────────────────────────────────┐ │
│ │ Valor: R$ [___________]                     │ │
│ └─────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ BLOCO 2: DADOS DO DOCUMENTO FISCAL              │
│ ┌─────────────────────────────────────────────┐ │
│ │ Tipo: [Selecione...]  Número: [_____]      │ │
│ │ Série: [___]  Data Emissão: [__/__/____]   │ │
│ │ Observação (opcional): [________________]   │ │
│ └─────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ BLOCO 3: FORMA DE PAGAMENTO                     │
│ ┌─────────────────────────────────────────────┐ │
│ │ ○ Conta Bancária  ○ Documento/Fatura       │ │
│ │                                             │ │
│ │ [Se conta bancária for selecionada]        │ │
│ │ Banco: [_________]  Agência: [____]-[_]    │ │
│ │ Conta: [_______]-[_]  Operação: [___]      │ │
│ │ Cidade: [______________]                    │ │
│ └─────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ BLOCO 4: OBSERVAÇÃO DO PAGAMENTO (opcional)     │
│ ┌─────────────────────────────────────────────┐ │
│ │ [________________________________]          │ │
│ │ [________________________________]          │ │
│ └─────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘

       [Cancelar]  [Salvar e Continuar]
```

---

## 🎉 ALTERAÇÕES IMPLEMENTADAS

✅ Adicionado campo `observacao_documento` no Bloco 2 (opcional)  
✅ Adicionado campo `observacao_pagamento` no Bloco 4 (opcional)  
✅ Forma de pagamento `documento_fatura` agora funciona corretamente  
✅ Campos bancários são obrigatórios apenas quando `forma_pagamento = "conta_bancaria"`  
✅ Validações ajustadas para aceitar `documento_fatura` sem dados bancários  

---

**🔥 Backend atualizado e pronto para integração!**
