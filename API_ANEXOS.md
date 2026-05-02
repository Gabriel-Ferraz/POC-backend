# 📎 API - Gerenciamento de Anexos de Solicitação de Pagamento

## 📋 Endpoints Implementados

---

## 1. Listar Anexos da Solicitação

**Endpoint:**
```
GET /api/solicitacoes/{id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "solicitacao": {
    "id": 1,
    "numero": "SP-2024-000001",
    "status": "Pendente",
    "anexos": [
      {
        "id": 1,
        "tipo_anexo": "Documento Fiscal (NF, Recibo, Guias, Faturas, etc.)",
        "arquivo_path": "/storage/anexos/nf_001.pdf",
        "arquivo_nome": "NF.pdf",
        "status": "Aguardando Aprovação",
        "data_envio": "2024-12-31",
        "motivo_recusa": null
      },
      {
        "id": 2,
        "tipo_anexo": "Certidão Negativa de Débitos",
        "arquivo_path": null,
        "arquivo_nome": null,
        "status": "Pendente",
        "data_envio": null,
        "motivo_recusa": null
      }
    ]
  }
}
```

---

## 2. Upload de Anexo

**Endpoint:**
```
POST /api/solicitacoes/{solicitacao_id}/anexos/{anexo_id}/upload
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body (FormData):**
- `arquivo` (file, required) - Arquivo PDF (máximo 10MB)

**Response Success (200):**
```json
{
  "message": "Anexo enviado com sucesso",
  "anexo": {
    "id": 1,
    "tipo_anexo": "Documento Fiscal (NF, Recibo, Guias, Faturas, etc.)",
    "arquivo_path": "/storage/anexos/nf_001.pdf",
    "arquivo_nome": "NF.pdf",
    "status": "Anexo Cadastrado",
    "data_envio": "2024-12-31"
  }
}
```

**Response Errors:**

### 403 - Sem permissão
```json
{
  "message": "Você não tem permissão para enviar anexos desta solicitação"
}
```

### 400 - Anexo já aprovado
```json
{
  "message": "Não é possível substituir um anexo já aprovado"
}
```

### 422 - Validação falhou
```json
{
  "message": "Os dados fornecidos são inválidos",
  "errors": {
    "arquivo": [
      "O campo arquivo é obrigatório",
      "O arquivo deve ser um PDF",
      "O arquivo não pode ser maior que 10MB"
    ]
  }
}
```

---

## 3. Remover Anexo

**Endpoint:**
```
POST /api/solicitacoes/{solicitacao_id}/anexos/{anexo_id}
```
ou
```
DELETE /api/solicitacoes/{solicitacao_id}/anexos/{anexo_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "message": "Anexo removido com sucesso"
}
```

**Response Errors:**

### 403 - Sem permissão
```json
{
  "message": "Você não tem permissão para remover anexos desta solicitação"
}
```

### 400 - Anexo já aprovado
```json
{
  "message": "Não é possível remover um anexo já aprovado"
}
```

---

## 4. Download/Visualizar Anexo

**Endpoint:**
```
GET /api/solicitacoes/{solicitacao_id}/anexos/{anexo_id}/download
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
- Retorna o arquivo PDF para download/visualização

**Response Errors:**

### 403 - Sem permissão
```json
{
  "message": "Você não tem permissão para visualizar este anexo"
}
```

### 404 - Arquivo não encontrado
```json
{
  "message": "Anexo não encontrado"
}
```

---

## 5. Enviar Todos os Anexos para Aprovação

**Endpoint:**
```
POST /api/solicitacoes/{solicitacao_id}/anexos/enviar-todos
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "message": "Anexos enviados para aprovação com sucesso"
}
```

**Response Errors:**

### 400 - Anexos pendentes
```json
{
  "message": "Existem anexos pendentes de envio",
  "anexos_pendentes": [
    "Certidão Negativa de Débitos",
    "FGTS"
  ]
}
```

### 403 - Sem permissão
```json
{
  "message": "Você não tem permissão para enviar anexos desta solicitação"
}
```

---

## 6. Aprovar Anexo (Gestor)

**Endpoint:**
```
POST /api/anexos/{anexo_id}/aprovar
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "message": "Anexo aprovado com sucesso"
}
```

**Response Errors:**

### 403 - Não é gestor
```json
{
  "message": "Apenas gestores de contrato podem aprovar anexos"
}
```

### 400 - Status inválido
```json
{
  "message": "Anexo não está aguardando aprovação"
}
```

---

## 7. Recusar Anexo (Gestor)

**Endpoint:**
```
POST /api/anexos/{anexo_id}/recusar
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "motivo": "Certidão vencida. Por favor, envie uma certidão atualizada."
}
```

**Response Success (200):**
```json
{
  "message": "Anexo recusado com sucesso"
}
```

**Response Errors:**

### 403 - Não é gestor
```json
{
  "message": "Apenas gestores de contrato podem recusar anexos"
}
```

### 400 - Status inválido
```json
{
  "message": "Anexo não está aguardando aprovação"
}
```

### 422 - Motivo inválido
```json
{
  "message": "Os dados fornecidos são inválidos",
  "errors": {
    "motivo": [
      "O campo motivo é obrigatório",
      "O campo motivo deve ter pelo menos 10 caracteres"
    ]
  }
}
```

---

## 🎯 Tipos de Anexos Obrigatórios

Os 5 tipos padrões criados automaticamente ao criar uma solicitação:

1. **documento_fiscal** → "Documento Fiscal (NF, Recibo, Guias, Faturas, etc.)"
2. **certidao_negativa_debitos** → "Certidão Negativa de Débitos"
3. **certidao_tributaria** → "Certidão Tributária"
4. **guia_previdencia_social** → "Guia de Previdência Social"
5. **fgts** → "FGTS"

---

## 🎯 Status dos Anexos

| Status | Label | Descrição |
|--------|-------|-----------|
| `pendente` | Pendente | Anexo ainda não foi enviado |
| `anexo_cadastrado` | Anexo Cadastrado | Arquivo foi enviado mas ainda não foi para aprovação |
| `aguardando_aprovacao` | Aguardando Aprovação | Anexo enviado e aguardando análise do Gestor |
| `aprovado` | Aprovado | Anexo aprovado pelo Gestor |
| `recusado` | Recusado | Anexo recusado pelo Gestor (com motivo) |

---

## 🔄 Fluxo de Aprovação

### 1️⃣ Responsável Técnico envia anexos:

1. Faz upload de cada anexo individualmente
   - Status do anexo muda para `anexo_cadastrado`
   - `data_envio` é preenchida

2. Quando todos estiverem enviados, clica em "Enviar todos para aprovação"
   - Status de todos os anexos muda para `aguardando_aprovacao`
   - Status da solicitação muda para `aguardando_aprovacao_anexos`
   - Trâmite é registrado

### 2️⃣ Gestor do Contrato avalia anexos:

**Se todos os anexos forem aprovados:**
- Status de cada anexo muda para `aprovado`
- Status da solicitação muda para `aguardando_autorizacao_gestor`
- Trâmite é registrado
- Solicitação prossegue no fluxo interno da PMSJP

**Se algum anexo for recusado (exceto Documento Fiscal):**
- Status do anexo muda para `recusado`
- Status da solicitação muda para `anexos_recusados`
- Responsável Técnico pode corrigir enviando novo anexo
- Após correção, pode enviar novamente para aprovação

**Se o Documento Fiscal/Recibo for recusado:**
- Status do anexo muda para `recusado`
- Status da solicitação muda para `anexos_recusados`
- ⚠️ **ATENÇÃO:** Documento Fiscal recusado não pode ser corrigido
- Responsável Técnico deve **cancelar** a solicitação
- Criar uma nova solicitação com documento correto

---

## 🔐 Permissões

### Responsável Técnico (solicitante_id):
- ✅ Upload de anexos
- ✅ Remover anexos (apenas pendente/anexo_cadastrado/recusado)
- ✅ Enviar todos para aprovação
- ✅ Download/visualizar anexos

### Gestor do Contrato (perfil = "gestor_contrato"):
- ✅ Aprovar anexos
- ✅ Recusar anexos
- ✅ Download/visualizar anexos

### Outros perfis:
- ❌ Acesso negado (403)

---

## 📦 Estrutura do Banco de Dados

### Tabela: `anexos_solicitacao`

```sql
CREATE TABLE anexos_solicitacao (
    id BIGINT PRIMARY KEY,
    solicitacao_id BIGINT NOT NULL,
    tipo_anexo ENUM('documento_fiscal', 'certidao_negativa_debitos', 'certidao_tributaria', 'guia_previdencia_social', 'fgts'),
    arquivo_path VARCHAR(255) NULL,
    arquivo_nome VARCHAR(255) NULL,
    status ENUM('pendente', 'anexo_cadastrado', 'aguardando_aprovacao', 'aprovado', 'recusado') DEFAULT 'pendente',
    data_envio DATE NULL,
    motivo_recusa TEXT NULL,
    aprovado_por BIGINT NULL,
    data_aprovacao DATETIME NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (solicitacao_id) REFERENCES solicitacoes_pagamento(id) ON DELETE CASCADE,
    FOREIGN KEY (aprovado_por) REFERENCES users(id) ON DELETE SET NULL
);
```

---

## ✅ Regras de Validação

### Upload de Anexo:
- Formato: Apenas PDF
- Tamanho: Máximo 10MB (10240 KB)
- Permissão: Apenas o dono da solicitação
- Anexos aprovados não podem ser substituídos

### Remover Anexo:
- Permissão: Apenas o dono da solicitação
- Anexos aprovados não podem ser removidos
- Remove arquivo físico do storage

### Enviar Todos para Aprovação:
- Todos os anexos devem ter arquivo
- Permissão: Apenas o dono da solicitação

### Aprovar/Recusar:
- Permissão: Apenas gestores de contrato
- Anexo deve estar com status `aguardando_aprovacao`
- Motivo de recusa: Mínimo 10 caracteres, máximo 500

---

## 🎉 IMPLEMENTAÇÃO COMPLETA

✅ Todos os endpoints criados e testados  
✅ Validações de permissão implementadas  
✅ Fluxo de aprovação automático  
✅ Status e labels traduzidos  
✅ Download de arquivos funcionando  
✅ Regra especial para Documento Fiscal recusado  
✅ Migrations e seeders atualizados  

**🚀 API pronta para integração com o frontend!**
