# 📎 FLUXO DE ANEXOS - APROVAÇÃO PELO GESTOR

## 🔄 Fluxo Completo

### 1️⃣ Responsável Técnico (João Silva) - CPF: 12345678900

**Ações:**
1. Acessa Portal do Fornecedor
2. Cria nova solicitação de pagamento
3. Sistema cria automaticamente 5 anexos obrigatórios (status: `pendente`)
4. Faz upload de cada anexo (PDF, imagem)
5. Clica em "Enviar todos para aprovação"
6. Status da solicitação muda para `aguardando_aprovacao_anexos`

---

### 2️⃣ Gestor Contrato (Maria Santos) - CPF: 98765432100

**Ações:**
1. Acessa tela "Aprovar Anexos"
2. Vê lista de solicitações pendentes de aprovação
3. Clica em uma solicitação para ver detalhes
4. Visualiza cada anexo
5. Aprova ou recusa cada anexo
   - Se aprovar: anexo fica `aprovado`
   - Se recusar: deve informar motivo (obrigatório)
6. Se todos os anexos forem aprovados: status muda para `aguardando_autorizacao_gestor`
7. Se algum anexo for recusado: status muda para `anexos_recusados`

---

### 3️⃣ Responsável Técnico (reenvio)

**Se algum anexo foi recusado:**
1. Vê o motivo da recusa
2. Faz upload novamente do anexo correto
3. Clica em "Enviar todos para aprovação" novamente
4. Volta para aprovação do gestor

---

## 📡 ENDPOINTS PARA O FRONTEND

### Para o Gestor (Maria Santos)

#### Listar Solicitações Pendentes de Aprovação
```http
GET /api/gestor/solicitacoes-pendentes
Authorization: Bearer {token}

Response:
{
  "solicitacoes": [
    {
      "id": 1,
      "numero": "SP-2024-000001",
      "valor": 12000.00,
      "status": "aguardando_aprovacao_anexos",
      "data": "02/05/2024 10:30",
      "solicitante": "João Silva (Responsável Técnico)",
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

#### Ver Detalhes da Solicitação (com anexos)
```http
GET /api/gestor/solicitacoes/{id}
Authorization: Bearer {token}

Response:
{
  "solicitacao": {
    "id": 1,
    "numero": "SP-2024-000001",
    "valor": 12000.00,
    "status": "aguardando_aprovacao_anexos",
    "data": "02/05/2024 10:30",
    "solicitante": "João Silva (Responsável Técnico)",
    "fornecedor": "Fornecedor Demonstração LTDA",
    "cnpj": "12.345.678/0001-90",
    "empenho": "934/2023",
    "contrato": "Contrato 154/2023",
    "documento_fiscal": {
      "tipo": "Nota Fiscal",
      "numero": "12345",
      "serie": "001",
      "data_emissao": "25/01/2024"
    }
  },
  "anexos": [
    {
      "id": 1,
      "tipo_anexo": "documento_fiscal",
      "tipo_anexo_label": "Documento Fiscal / Recibo",
      "arquivo": "anexos/solicitacoes/abc123.pdf",
      "status": "aguardando_aprovacao",
      "motivo_recusa": null,
      "avaliado_por": null,
      "avaliado_em": null,
      "data_envio": "02/05/2024 10:35"
    },
    {
      "id": 2,
      "tipo_anexo": "certidao_negativa_debitos",
      "tipo_anexo_label": "Certidão Negativa de Débitos",
      "arquivo": "anexos/solicitacoes/def456.pdf",
      "status": "aguardando_aprovacao",
      "motivo_recusa": null,
      "avaliado_por": null,
      "avaliado_em": null,
      "data_envio": "02/05/2024 10:36"
    }
    // ... outros 3 anexos
  ]
}
```

#### Aprovar Anexo
```http
POST /api/anexos/{anexoId}/aprovar
Authorization: Bearer {token}

Response:
{
  "message": "Anexo aprovado com sucesso"
}
```

#### Recusar Anexo (com motivo obrigatório)
```http
POST /api/anexos/{anexoId}/recusar
Authorization: Bearer {token}
Content-Type: application/json

{
  "motivo": "Documento está ilegível, favor reenviar com melhor qualidade"
}

Response:
{
  "message": "Anexo recusado com sucesso"
}
```

#### Download do Anexo
```http
GET /api/anexos/{anexoId}/download
Authorization: Bearer {token}

Response: arquivo binário (PDF, imagem, etc)
```

---

## 🎯 Status dos Anexos

| Status | Descrição |
|--------|-----------|
| `pendente` | Anexo criado, aguardando upload |
| `anexo_cadastrado` | Upload feito, não enviado para aprovação |
| `aguardando_aprovacao` | Enviado para aprovação do gestor |
| `aprovado` | Aprovado pelo gestor |
| `recusado` | Recusado pelo gestor (com motivo) |

---

## 🎯 Status da Solicitação

| Status | Descrição |
|--------|-----------|
| `pendente` | Criada, aguardando anexos |
| `aguardando_aprovacao_anexos` | Anexos enviados, aguardando gestor |
| `anexos_recusados` | Algum anexo foi recusado |
| `aguardando_autorizacao_gestor` | Todos anexos aprovados |

---

## ✅ RESUMO PARA O FRONTEND

### Tela do Gestor (Maria Santos)

**URL:** `/gestor/solicitacoes` ou `/gestor/aprovar-anexos`

**Chamadas da API:**

1. **Listar solicitações pendentes:**
   ```
   GET /api/gestor/solicitacoes-pendentes
   ```

2. **Ver detalhes de uma solicitação:**
   ```
   GET /api/gestor/solicitacoes/{id}
   ```

3. **Aprovar um anexo:**
   ```
   POST /api/anexos/{anexoId}/aprovar
   ```

4. **Recusar um anexo:**
   ```
   POST /api/anexos/{anexoId}/recusar
   Body: { "motivo": "..." }
   ```

5. **Download do anexo para visualização:**
   ```
   GET /api/anexos/{anexoId}/download
   ```

---

## 📋 Tipos de Anexo Obrigatórios

1. `documento_fiscal` → "Documento Fiscal / Recibo"
2. `certidao_negativa_debitos` → "Certidão Negativa de Débitos"
3. `certidao_tributaria` → "Certidão Tributária"
4. `guia_previdencia_social` → "Guia de Previdência Social (GPS)"
5. `fgts` → "FGTS"

---

## 🔐 Permissões

✅ Apenas usuários com `perfil = "gestor_contrato"` podem:
- Listar solicitações pendentes
- Aprovar anexos
- Recusar anexos

❌ Outros perfis recebem erro 403 (Acesso Negado)

---

**🎉 Endpoints criados e prontos para uso!**
