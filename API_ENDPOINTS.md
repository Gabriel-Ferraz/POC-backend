# 📡 API ENDPOINTS - RESUMO RÁPIDO

## Base URL
```
http://localhost:8000/api
```

## 🔐 Autenticação

### Login
```http
POST /auth/login
Content-Type: application/json

{
  "cpf": "12345678900",  // ou "email": "email@exemplo.com"
  "password": "senha123"
}

Response:
{
  "token": "1|abc123...",
  "user": { id, name, cpf, perfil, fornecedor }
}
```

### Dados do Usuário Logado
```http
GET /auth/me
Authorization: Bearer {token}

Response:
{
  "user": { id, name, cpf, perfil, fornecedor }
}
```

### Logout
```http
POST /auth/logout
Authorization: Bearer {token}
```

---

## 🏢 Portal do Fornecedor

### Listar Empenhos
```http
GET /fornecedor/empenhos
Authorization: Bearer {token}

Response:
{
  "fornecedor": { id, nome, cnpj },
  "empenhos": [
    {
      "id": 1,
      "numero": "934/2023",
      "contrato": "Contrato 154/2023",
      "data_emissao": "01/02/2023",
      "valor": 150000.00,
      "saldo": 45000.00,
      "status": "disponivel"
    }
  ]
}
```

### Detalhe do Empenho
```http
GET /fornecedor/empenhos/{id}
Authorization: Bearer {token}
```

---

## 💰 Solicitações de Pagamento

### Listar Solicitações do Empenho
```http
GET /empenhos/{empenhoId}/solicitacoes
Authorization: Bearer {token}

Response:
{
  "empenho": { id, numero, saldo },
  "solicitacoes": [
    {
      "id": 1,
      "numero": "SP-2024-000001",
      "data": "01/05/2024",
      "valor": 12000.00,
      "solicitante": "João Silva",
      "status": "pendente",
      "documento_fiscal": "NF 12345"
    }
  ]
}
```

### Criar Solicitação
```http
POST /empenhos/{empenhoId}/solicitacoes
Authorization: Bearer {token}
Content-Type: application/json

{
  "valor": 12000.00,
  "observacao": "Pagamento ref. janeiro/2024",
  "tipo_documento": "Nota Fiscal",
  "numero_documento": "12345",
  "serie": "001",
  "data_emissao_documento": "2024-01-25",
  "observacao_documento": "NF-e de serviços",
  "forma_pagamento": "conta_bancaria",
  "banco": "Banco do Brasil",
  "agencia": "1234",
  "digito_agencia": "5",
  "conta": "567890",
  "digito_conta": "1",
  "operacao": "001",
  "cidade_banco": "São José dos Pinhais"
}

Response:
{
  "message": "Solicitação criada com sucesso",
  "solicitacao": { id, numero, valor, status }
}
```

### Detalhe da Solicitação (com abas)
```http
GET /solicitacoes/{id}
Authorization: Bearer {token}

Response:
{
  "solicitacao": {
    "id": 1,
    "numero": "SP-2024-000001",
    "valor": 12000.00,
    "status": "pendente",
    "data": "01/05/2024 10:30",
    "solicitante": "João Silva",
    "fornecedor": "Fornecedor Demo LTDA",
    "empenho": "934/2023",
    "documento_fiscal": {...},
    "forma_pagamento": {...},
    "cancelamento": null,
    "pagamento": null
  },
  "anexos": [...],
  "tramites": [...]
}
```

### Cancelar Solicitação
```http
POST /solicitacoes/{id}/cancelar
Authorization: Bearer {token}
Content-Type: application/json

{
  "motivo": "Documento fiscal com dados incorretos"
}

Response:
{
  "message": "Solicitação cancelada com sucesso"
}
```

### Histórico de Trâmites
```http
GET /solicitacoes/{id}/tramites
Authorization: Bearer {token}

Response:
{
  "tramites": [
    {
      "fase": "Solicitação Criada",
      "usuario": "João Silva",
      "observacao": "...",
      "motivo": null,
      "data": "01/05/2024 10:30:00"
    }
  ]
}
```

---

## 📎 Anexos

### Listar Anexos da Solicitação
```http
GET /solicitacoes/{solicitacaoId}/anexos
Authorization: Bearer {token}

Response:
{
  "solicitacao_id": 1,
  "solicitacao_numero": "SP-2024-000001",
  "solicitacao_status": "pendente",
  "anexos": [
    {
      "id": 1,
      "tipo_anexo": "documento_fiscal",
      "arquivo": "anexos/...",
      "status": "pendente",
      "motivo_recusa": null,
      "avaliado_por": null,
      "avaliado_em": null,
      "data_envio": "01/05/2024 10:30"
    }
  ]
}
```

### Upload de Anexo
```http
POST /solicitacoes/{solicitacaoId}/anexos
Authorization: Bearer {token}
Content-Type: multipart/form-data

anexo_id=1
arquivo=[file]

Response:
{
  "message": "Anexo enviado com sucesso",
  "anexo": { id, tipo_anexo, arquivo, status }
}
```

### Enviar Todos para Aprovação
```http
POST /solicitacoes/{solicitacaoId}/anexos/enviar-todos
Authorization: Bearer {token}

Response:
{
  "message": "Anexos enviados para aprovação com sucesso"
}
```

### Aprovar Anexo (Gestor)
```http
POST /anexos/{anexoId}/aprovar
Authorization: Bearer {token}

Response:
{
  "message": "Anexo aprovado com sucesso"
}
```

### Recusar Anexo (Gestor)
```http
POST /anexos/{anexoId}/recusar
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

### Remover Anexo
```http
DELETE /anexos/{anexoId}
Authorization: Bearer {token}
```

### Download de Anexo
```http
GET /anexos/{anexoId}/download
Authorization: Bearer {token}

Response: arquivo binário
```

---

## 🎫 Suporte ao Usuário

### Listar Chamados
```http
GET /chamados
Authorization: Bearer {token}

Query params (opcionais):
?id=1
&data_cadastro_inicio=2024-01-01
&data_cadastro_fim=2024-05-31
&modulo=Portal%20do%20Fornecedor
&usuario_origem=João
&assunto=dúvida
&status=aberto

Response:
{
  "chamados": [
    {
      "id": 1,
      "modulo": "Portal do Fornecedor",
      "assunto": "Dúvida sobre anexos",
      "usuario": "João Silva",
      "status": "aberto",
      "data_cadastro": "01/05/2024 10:30",
      "data_resposta": null,
      "total_mensagens": 1
    }
  ]
}
```

### Criar Chamado
```http
POST /chamados
Authorization: Bearer {token}
Content-Type: multipart/form-data

modulo=Portal do Fornecedor
assunto=Dúvida sobre anexação
mensagem=Gostaria de saber...
anexos[]=[file1]
anexos[]=[file2]

Response:
{
  "message": "Chamado criado com sucesso",
  "chamado": { id, numero, status }
}
```

### Detalhe do Chamado (Timeline)
```http
GET /chamados/{id}
Authorization: Bearer {token}

Response:
{
  "chamado": { id, modulo, assunto, usuario, status, ... },
  "timeline": [
    {
      "tipo": "abertura",
      "usuario": "João Silva",
      "mensagem": "...",
      "data": "01/05/2024 10:30:00",
      "anexos": [...]
    },
    {
      "tipo": "resposta",
      "usuario": "Ana Paula",
      "mensagem": "...",
      "data": "01/05/2024 14:20:00",
      "anexos": []
    }
  ]
}
```

### Responder Chamado
```http
POST /chamados/{id}/responder
Authorization: Bearer {token}
Content-Type: multipart/form-data

mensagem=Respondendo sua dúvida...
anexos[]=[file]

Response:
{
  "message": "Resposta enviada com sucesso"
}
```

### Anexar Arquivo
```http
POST /chamados/{id}/anexos
Authorization: Bearer {token}
Content-Type: multipart/form-data

arquivo=[file]
```

### Concluir Chamado
```http
POST /chamados/{id}/concluir
Authorization: Bearer {token}

Response:
{
  "message": "Chamado concluído com sucesso"
}
```

---

## 📊 Prestação de Contas

### Exportar
```http
POST /prestacao-contas/exportar
Authorization: Bearer {token}
Content-Type: application/json

{
  "ano": 2024,
  "modulo": "Financeiro",
  "tipo_geracao": "Mensal",
  "mes": 5,
  "arquivos_selecionados": [
    "PlanoContabil",
    "MovimentoMensal",
    "Balancete"
  ]
}

Response:
{
  "message": "Exportação realizada com sucesso",
  "exportacao": {
    "id": 1,
    "arquivo": "prestacao_contas_2024_Financeiro_1234567890.txt",
    "data_exportacao": "02/05/2024 10:30",
    "quantidade_registros": 125
  }
}
```

### Listar Exportações
```http
GET /prestacao-contas/exportacoes
Authorization: Bearer {token}

Response:
{
  "exportacoes": [
    {
      "id": 1,
      "ano": 2024,
      "modulo": "Financeiro",
      "tipo_geracao": "Mensal",
      "mes": 5,
      "arquivos": ["PlanoContabil", "Balancete"],
      "arquivo_gerado": "prestacao_contas_2024.txt",
      "quantidade_registros": 125,
      "data": "02/05/2024 10:30"
    }
  ]
}
```

### Download Exportação
```http
GET /prestacao-contas/exportacoes/{id}/download
Authorization: Bearer {token}

Response: arquivo binário (.txt)
```

---

## 💼 Orçamentário

### Listar Leis/Atos
```http
GET /orcamentario/leis-atos
Authorization: Bearer {token}

Response:
{
  "leis_atos": [
    {
      "id": 1,
      "numero": "Lei 2.345/2024",
      "tipo": "lei",
      "data_ato": "10/01/2024",
      "data_publicacao": "12/01/2024",
      "descricao": "...",
      "arquivo": "..."
    }
  ]
}
```

### Cadastrar Lei/Ato
```http
POST /orcamentario/leis-atos
Authorization: Bearer {token}
Content-Type: multipart/form-data

numero=Lei 2.345/2024
tipo=lei
data_ato=2024-01-10
data_publicacao=2024-01-12
descricao=Autoriza abertura...
arquivo=[file.pdf]

Response:
{
  "message": "Lei/Ato cadastrado com sucesso",
  "lei_ato": { id, numero, tipo }
}
```

### Atualizar Lei/Ato
```http
PUT /orcamentario/leis-atos/{id}
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

### Excluir Lei/Ato
```http
DELETE /orcamentario/leis-atos/{id}
Authorization: Bearer {token}
```

### Listar Alterações Orçamentárias
```http
GET /orcamentario/alteracoes
Authorization: Bearer {token}

Response:
{
  "alteracoes": [
    {
      "id": 1,
      "lei_ato": "Lei 2.345/2024",
      "decreto_autorizador": "Decreto 100/2024",
      "tipo_ato": "decreto",
      "tipo_credito": "suplementar",
      "tipo_recurso": "superavit",
      "valor_credito": 500000.00,
      "data_ato": "15/01/2024",
      "total_dotacoes": 2
    }
  ]
}
```

### Criar Alteração Orçamentária
```http
POST /orcamentario/alteracoes
Authorization: Bearer {token}
Content-Type: application/json

{
  "lei_ato_id": 1,
  "decreto_autorizador": "Decreto 100/2024",
  "data_ato": "2024-01-15",
  "data_publicacao": "2024-01-16",
  "tipo_ato": "decreto",
  "tipo_credito": "suplementar",
  "tipo_recurso": "superavit",
  "valor_credito": 500000.00
}

Response:
{
  "message": "Alteração orçamentária criada com sucesso",
  "alteracao": { id, decreto }
}
```

### Detalhe da Alteração
```http
GET /orcamentario/alteracoes/{id}
Authorization: Bearer {token}

Response:
{
  "alteracao": { ... },
  "dotacoes": [
    {
      "dotacao_orcamentaria": "10.101.10.122.0001.2.001",
      "conta_receita": null,
      "valor_suprimido": 0.00,
      "valor_suplementado": 250000.00,
      "saldo_atual": 1000000.00,
      "novo_saldo": 1250000.00
    }
  ]
}
```

### Adicionar Dotação
```http
POST /orcamentario/alteracoes/{id}/dotacoes
Authorization: Bearer {token}
Content-Type: application/json

{
  "dotacao_orcamentaria": "10.101.10.122.0001.2.001",
  "conta_receita": null,
  "valor_suprimido": 0.00,
  "valor_suplementado": 250000.00,
  "saldo_atual": 1000000.00
}

Response:
{
  "message": "Dotação adicionada com sucesso",
  "dotacao": { id, novo_saldo }
}
```

### Gerar Dados para PDF
```http
GET /orcamentario/alteracoes/{id}/pdf
Authorization: Bearer {token}

Response:
{
  "message": "Dados para geração de PDF",
  "pdf_data": {
    "titulo": "ALTERAÇÃO ORÇAMENTÁRIA",
    "dados_lei": {...},
    "dados_decreto": {...},
    "tipo_credito": "SUPLEMENTAR",
    "tipo_recurso": "SUPERAVIT",
    "valor_credito": "500.000,00",
    "dotacoes": [...],
    "data_geracao": "02/05/2024 10:30"
  }
}
```

---

## 🎯 TIPOS E ENUMS

### Perfis
- `responsavel_tecnico`
- `gestor_contrato`
- `operador_pmsjp`
- `gestor_suporte`
- `usuario_comum`
- `operador_orcamentario`

### Status Solicitação
- `pendente`
- `aguardando_aprovacao_anexos`
- `anexos_recusados`
- `aguardando_autorizacao_gestor`
- `em_liquidacao`
- `em_ordem_pagamento`
- `pagamento_em_remessa`
- `pagamento_realizado`
- `cancelada`

### Status Anexo
- `pendente`
- `anexo_cadastrado`
- `aguardando_aprovacao`
- `aprovado`
- `recusado`

### Tipos de Anexo
- `documento_fiscal`
- `certidao_negativa_debitos`
- `certidao_tributaria`
- `guia_previdencia_social`
- `fgts`

### Status Chamado
- `aberto`
- `em_atendimento`
- `concluido`

### Status Empenho
- `disponivel`
- `bloqueado`
- `sem_saldo`

### Tipos de Documento (Lei/Ato)
- `lei`
- `decreto`
- `resolucao`
- `ato_gestor`

### Tipos de Crédito
- `especial`
- `suplementar`
- `extraordinario`

### Tipos de Recurso
- `superavit`
- `excesso_arrecadacao`

---

## 🔒 Autenticação

Todas as rotas (exceto `/auth/login`, `/auth/register`, `/auth/forgot-password` e `/auth/reset-password`) requerem autenticação via Bearer Token.

Incluir em todas as requisições autenticadas:
```
Authorization: Bearer SEU_TOKEN_AQUI
```

---

## 📋 Formato de Erros

### Validação (422)
```json
{
  "message": "Dados inválidos",
  "errors": {
    "valor": ["O campo valor é obrigatório."],
    "cpf": ["O CPF informado é inválido."]
  }
}
```

### Autorização (401)
```json
{
  "message": "Não autorizado"
}
```

### Não Encontrado (404)
```json
{
  "message": "Recurso não encontrado"
}
```

### Erro de Negócio (400)
```json
{
  "message": "Saldo insuficiente no empenho"
}
```

---

**📚 Para mais detalhes, consulte: `IMPLEMENTACAO_POC.md`**
