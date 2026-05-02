# 📚 DOCUMENTAÇÃO COMPLETA - API de Chamados

## 🎯 VISÃO GERAL

Sistema de suporte com abertura de chamados, histórico de mensagens (timeline) e anexos.

**Base URL:** `http://localhost:3333/api`

**Autenticação:** Todas as rotas requerem `Authorization: Bearer {token}`

---

## 📡 ENDPOINTS

### 1. Criar Chamado

**POST** `/api/chamados`

Cria um novo chamado de suporte com anexos opcionais.

#### Headers
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

#### Body (FormData)
```javascript
{
  modulo: string,      // obrigatório, max 255 caracteres
  assunto: string,     // obrigatório, campo TEXT (sem limite)
  anexos[]: File[]     // opcional, múltiplos arquivos
}
```

#### Validações
- `modulo`: obrigatório, string, max 255
- `assunto`: obrigatório, string (TEXT - pode ser grande)
- `anexos`: opcional, array
  - Cada arquivo: max 10MB
  - Formatos aceitos: pdf, doc, docx, xls, xlsx, png, jpg, jpeg, gif

#### Exemplo de Request (JavaScript)
```javascript
const formData = new FormData()
formData.append('modulo', 'Portal do Fornecedor')
formData.append('assunto', 'Erro ao acessar sistema - Não consigo fazer login no portal')
formData.append('anexos[]', file1) // File object
formData.append('anexos[]', file2) // File object

const response = await fetch('http://localhost:3333/api/chamados', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: formData
})
```

#### Response (201 Created)
```json
{
  "message": "Chamado criado com sucesso",
  "chamado": {
    "id": 5,
    "protocolo": "#5",
    "modulo": "Portal do Fornecedor",
    "assunto": "Erro ao acessar sistema - Não consigo fazer login no portal",
    "usuario": "João Silva (Responsável Técnico)",
    "status": "aberto",
    "data_cadastro": "02/05/2026 14:30",
    "data_abertura": "02/05/2026",
    "anexos_count": 2
  }
}
```

#### Errors
**422 Unprocessable Entity**
```json
{
  "message": "Dados inválidos",
  "errors": {
    "modulo": ["The modulo field is required."],
    "assunto": ["The assunto field is required."]
  }
}
```

**401 Unauthorized**
```json
{
  "message": "Unauthenticated",
  "errors": null
}
```

#### O que acontece no Backend:
1. ✅ Cria registro na tabela `chamados`
2. ✅ Cria mensagem de abertura na tabela `mensagens_chamado` com `tipo = 'abertura'`
3. ✅ Salva anexos em `storage/app/public/chamados/{chamado_id}/`
4. ✅ Registra anexos na tabela `anexos_chamado` vinculados à mensagem de abertura

---

### 2. Ver Detalhes do Chamado

**GET** `/api/chamados/{id}`

Retorna detalhes completos do chamado incluindo toda a timeline de mensagens e anexos.

#### Headers
```
Authorization: Bearer {token}
```

#### Path Parameters
- `id` (integer): ID do chamado

#### Exemplo de Request
```javascript
const response = await fetch('http://localhost:3333/api/chamados/3', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})

const data = await response.json()
```

#### Response (200 OK)
```json
{
  "chamado": {
    "id": 3,
    "protocolo": "#3",
    "modulo": "Portal do Fornecedor",
    "assunto": "Erro ao acessar sistema - Não consigo fazer login",
    "usuario": "João Silva (Responsável Técnico)",
    "status": "aberto",
    "data_abertura": "02/05/2026",
    "data_cadastro": "02/05/2026 14:16",
    "data_ultima_resposta": null,
    "data_conclusao": null,
    "mensagem_inicial": "Erro ao acessar sistema - Não consigo fazer login"
  },
  "timeline": [
    {
      "id": 1,
      "tipo": "abertura",
      "usuario": "João Silva (Responsável Técnico)",
      "mensagem": "Erro ao acessar sistema - Não consigo fazer login",
      "data": "02/05/2026 14:16",
      "anexos": [
        {
          "id": 1,
          "nome": "392-295-Notice-of-Allowance-20250905.pdf",
          "tamanho": "33.6 KB",
          "arquivo_path": "/api/chamados/anexos/1/download"
        }
      ]
    },
    {
      "id": 2,
      "tipo": "resposta",
      "usuario": "Ana Paula (Gestor Suporte)",
      "mensagem": "Estamos analisando o problema. Qual navegador você está usando?",
      "data": "02/05/2026 15:30",
      "anexos": []
    }
  ]
}
```

#### Estrutura da Timeline
Cada item da timeline contém:
- `id`: ID da mensagem
- `tipo`: `"abertura"` ou `"resposta"`
- `usuario`: Nome completo do usuário (com perfil)
- `mensagem`: Texto da mensagem
- `data`: Data/hora no formato `dd/mm/yyyy HH:mm`
- `anexos`: Array de anexos (pode estar vazio)

#### Estrutura de Anexo
Cada anexo contém:
- `id`: ID do anexo
- `nome`: Nome original do arquivo
- `tamanho`: Tamanho formatado (ex: "33.6 KB")
- `arquivo_path`: URL para download via API

#### Errors
**404 Not Found**
```json
{
  "message": "Resource not found",
  "errors": null
}
```

**403 Forbidden**
```json
{
  "message": "Você não tem permissão para visualizar este chamado.",
  "errors": null
}
```

#### Permissões
✅ **Pode visualizar:**
- Criador do chamado (usuário que abriu)
- Gestor de Suporte (`gestor_suporte`)
- Gestor de Contrato (`gestor_contrato`)

❌ **Não pode visualizar:**
- Outros usuários comuns

---

### 3. Download de Anexo

**GET** `/api/chamados/anexos/{id}/download`

Faz download de um anexo específico.

#### Headers
```
Authorization: Bearer {token}
```

#### Path Parameters
- `id` (integer): ID do anexo

#### Como usar no Frontend (com Blob URL)

**IMPORTANTE:** O token DEVE ser enviado via header `Authorization: Bearer {token}`. O anexo só pode ser baixado por usuários autenticados com permissão.

```javascript
const downloadAnexo = async (arquivo_path, nome) => {
  try {
    const token = localStorage.getItem('token')
    
    if (!token) {
      console.error('Token não encontrado')
      return
    }
    
    // 1. Fazer requisição com autenticação
    const response = await fetch(`http://localhost:3333${arquivo_path}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json, application/octet-stream'
      }
    })
    
    if (!response.ok) {
      const error = await response.json()
      console.error('Erro ao baixar anexo:', error)
      return
    }
    
    // 2. Converter para Blob
    const blob = await response.blob()
    
    // 3. Criar URL do Blob (tipo: blob:http://localhost:3000/...)
    const blobUrl = URL.createObjectURL(blob)
    
    // 4. Abrir em nova aba (para visualizar PDF, imagens, etc)
    window.open(blobUrl, '_blank')
    
    // OU para forçar download:
    // const link = document.createElement('a')
    // link.href = blobUrl
    // link.download = nome
    // link.click()
    // URL.revokeObjectURL(blobUrl)
  } catch (error) {
    console.error('Erro ao processar download:', error)
  }
}

// Uso em componente React:
<button onClick={() => downloadAnexo(anexo.arquivo_path, anexo.nome)}>
  {anexo.nome}
</button>
```

**Checklist de Debug se o download retornar 500:**
1. ✅ Verificar se o token está sendo enviado no header `Authorization`
2. ✅ Verificar se o token está válido (não expirado)
3. ✅ Verificar se o usuário tem permissão (criador ou gestor)
4. ✅ Verificar no Network do DevTools se o header está correto
5. ✅ Testar a mesma requisição no Postman/Insomnia com o token

#### Response
Retorna o arquivo binário com headers:
```
Content-Type: application/pdf (ou tipo apropriado)
Content-Disposition: attachment; filename="nome-do-arquivo.pdf"
```

#### Errors
**404 Not Found**
```json
{
  "message": "Anexo não encontrado"
}
```

```json
{
  "message": "Arquivo não encontrado no servidor"
}
```

**403 Forbidden**
```json
{
  "message": "Você não tem permissão para visualizar este anexo"
}
```

#### Permissões
✅ **Pode baixar:**
- Criador do chamado
- Gestor de Suporte
- Gestor de Contrato

❌ **Não pode baixar:**
- Outros usuários comuns

---

### 4. Responder Chamado

**POST** `/api/chamados/{id}/responder`

Adiciona uma nova resposta ao chamado com anexos opcionais.

#### Headers
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

#### Path Parameters
- `id` (integer): ID do chamado

#### Body (FormData)
```javascript
{
  mensagem: string,    // obrigatório
  anexos[]: File[]     // opcional, múltiplos arquivos
}
```

#### Validações
- `mensagem`: obrigatório, string
- `anexos`: opcional, array
  - Cada arquivo: max 10MB
  - Formatos: pdf, doc, docx, xls, xlsx, png, jpg, jpeg, gif

#### Exemplo de Request
```javascript
const formData = new FormData()
formData.append('mensagem', 'Estamos analisando o problema')
formData.append('anexos[]', file1)

const response = await fetch('http://localhost:3333/api/chamados/3/responder', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: formData
})
```

#### Response (200 OK)
```json
{
  "message": "Resposta adicionada com sucesso",
  "mensagem": {
    "id": 5,
    "tipo": "resposta",
    "usuario": "Ana Paula",
    "mensagem": "Estamos analisando o problema",
    "data": "02/05/2026 15:45",
    "anexos_count": 1
  }
}
```

#### Errors
**403 Forbidden**
```json
{
  "message": "Você não tem permissão para responder este chamado."
}
```

**400 Bad Request**
```json
{
  "message": "Chamado já foi concluído"
}
```

**422 Unprocessable Entity**
```json
{
  "message": "Dados inválidos",
  "errors": {
    "mensagem": ["The mensagem field is required."]
  }
}
```

#### O que acontece no Backend:
1. ✅ Cria nova mensagem com `tipo = 'resposta'`
2. ✅ Salva anexos vinculados à resposta
3. ✅ Atualiza `data_ultima_resposta` do chamado
4. ✅ Muda status para `em_atendimento` (se estava `aberto`)

#### Permissões
✅ **Pode responder:**
- Criador do chamado
- Gestor de Suporte
- Gestor de Contrato

❌ **Não pode responder:**
- Outros usuários comuns
- Ninguém (se chamado estiver concluído)

---

### 5. Listar Chamados (com filtros)

**GET** `/api/chamados`

Lista todos os chamados com filtros opcionais.

#### Headers
```
Authorization: Bearer {token}
```

#### Query Parameters (todos opcionais)

| Parâmetro | Tipo | Descrição | Exemplo |
|-----------|------|-----------|---------|
| `protocolo` | String | Número do protocolo (com ou sem #) | `protocolo=3` ou `protocolo=#3` |
| `modulo` | String | Busca parcial no módulo | `modulo=Portal` |
| `assunto` | String | Busca parcial no assunto | `assunto=erro` |
| `status` | String | Status (múltiplos separados por vírgula) | `status=aberto,em_atendimento` |
| `usuario_id` | Integer | ID do usuário (apenas gestores) | `usuario_id=1` |
| `data_cadastro_inicio` | Date | Data inicial (Y-m-d) | `data_cadastro_inicio=2026-05-01` |
| `data_cadastro_fim` | Date | Data final (Y-m-d) | `data_cadastro_fim=2026-05-31` |
| `data_resposta_inicio` | Date | Data inicial última resposta | `data_resposta_inicio=2026-05-01` |
| `data_resposta_fim` | Date | Data final última resposta | `data_resposta_fim=2026-05-31` |

#### Exemplo de Request
```javascript
const params = new URLSearchParams({
  status: 'aberto,em_atendimento',
  modulo: 'Portal',
  data_cadastro_inicio: '2026-05-01'
})

const response = await fetch(`http://localhost:3333/api/chamados?${params}`, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
```

#### Response (200 OK)
```json
{
  "chamados": [
    {
      "id": 3,
      "protocolo": "#3",
      "modulo": "Portal do Fornecedor",
      "assunto": "Erro ao acessar sistema",
      "usuario": "João Silva (Responsável Técnico)",
      "status": "aberto",
      "status_label": "Aberto",
      "data_abertura": "02/05/2026",
      "data_cadastro": "02/05/2026 14:16",
      "data_ultima_resposta": null,
      "total_mensagens": 1,
      "total_anexos": 2
    }
  ]
}
```

#### Regras de Permissão
- **Usuário Comum:** Vê apenas seus próprios chamados (filtro automático)
- **Gestor:** Vê todos os chamados (pode filtrar por usuário)

---

### 6. Listar Usuários (para filtro)

**GET** `/api/chamados/usuarios?busca={termo}`

Lista usuários para o filtro de chamados (com busca opcional).

#### Headers
```
Authorization: Bearer {token}
```

#### Query Parameters
- `busca` (opcional): Termo para buscar no nome (case-insensitive)

#### Response para Usuário Comum
```json
{
  "usuario_atual": {
    "id": 1,
    "name": "João Silva",
    "perfil": "responsavel_tecnico",
    "perfil_label": "Responsável Técnico"
  }
}
```

#### Response para Gestor
```json
{
  "usuarios": [
    {
      "id": 1,
      "name": "João Silva",
      "perfil": "responsavel_tecnico",
      "perfil_label": "Responsável Técnico"
    },
    {
      "id": 2,
      "name": "Maria Santos",
      "perfil": "gestor_contrato",
      "perfil_label": "Gestor do Contrato"
    }
  ]
}
```

**Observação:** Retorna apenas usuários que já criaram pelo menos 1 chamado.

---

## 🗄️ ESTRUTURA DO BANCO DE DADOS

### Tabela: `chamados`
```sql
- id (PK)
- usuario_id (FK → users)
- modulo (VARCHAR 255)
- assunto (TEXT)
- status (ENUM: 'aberto', 'em_atendimento', 'concluido')
- data_ultima_resposta (TIMESTAMP NULL)
- data_conclusao (TIMESTAMP NULL)
- created_at
- updated_at
```

### Tabela: `mensagens_chamado`
```sql
- id (PK)
- chamado_id (FK → chamados)
- usuario_id (FK → users)
- tipo (ENUM: 'abertura', 'resposta')
- mensagem (TEXT)
- created_at
- updated_at
```

### Tabela: `anexos_chamado`
```sql
- id (PK)
- chamado_id (FK → chamados)
- mensagem_id (FK → mensagens_chamado)
- nome_original (VARCHAR)
- nome_salvo (VARCHAR)
- caminho (VARCHAR)
- tamanho (BIGINT - bytes)
- tipo (VARCHAR - mime type)
- enviado_por_usuario_id (FK → users)
- created_at
- updated_at
```

---

## 🔄 FLUXO COMPLETO

### 1. Criar Chamado
```
Frontend → POST /api/chamados
         ↓
Backend cria:
  - Registro em chamados
  - Mensagem tipo 'abertura'
  - Anexos (se houver)
         ↓
Frontend recebe: { chamado: {...} }
```

### 2. Ver Detalhes
```
Frontend → GET /api/chamados/3
         ↓
Backend retorna:
  - Dados do chamado
  - Timeline completa (abertura + respostas)
  - Anexos de cada mensagem
         ↓
Frontend exibe: Timeline + Formulário de resposta
```

### 3. Responder Chamado
```
Frontend → POST /api/chamados/3/responder
         ↓
Backend cria:
  - Nova mensagem tipo 'resposta'
  - Anexos (se houver)
  - Atualiza data_ultima_resposta
  - Muda status se necessário
         ↓
Frontend recebe: { mensagem: {...} }
         ↓
Frontend recarrega: GET /api/chamados/3
```

### 4. Download de Anexo
```
Frontend clica no anexo
         ↓
Fetch → GET /api/chamados/anexos/1/download
        (com Authorization header)
         ↓
Backend verifica permissões
         ↓
Backend retorna arquivo binário
         ↓
Frontend cria Blob URL
         ↓
Abre em nova aba (blob:http://localhost:3000/...)
```

---

## 🎨 STATUS DO CHAMADO

### Estados possíveis:
- `aberto`: Recém criado, aguardando primeira resposta
- `em_atendimento`: Já foi respondido pelo menos uma vez
- `concluido`: Chamado finalizado

### Transições:
```
aberto → em_atendimento (ao receber primeira resposta)
em_atendimento → em_atendimento (respostas subsequentes)
qualquer → concluido (ao concluir via endpoint específico)
```

---

## 🔐 RESUMO DE PERMISSÕES

| Ação | Usuário Comum | Gestor Suporte | Gestor Contrato |
|------|---------------|----------------|-----------------|
| Criar chamado | ✅ | ✅ | ✅ |
| Ver próprios chamados | ✅ | ✅ | ✅ |
| Ver chamados de outros | ❌ | ✅ | ✅ |
| Responder próprio chamado | ✅ | ✅ | ✅ |
| Responder chamado de outro | ❌ | ✅ | ✅ |
| Filtrar por usuário | ❌ | ✅ | ✅ |
| Download anexos próprios | ✅ | ✅ | ✅ |
| Download anexos de outros | ❌ | ✅ | ✅ |

---

## 📝 OBSERVAÇÕES IMPORTANTES

1. **Campo `assunto`:**
   - É do tipo TEXT (sem limite de tamanho)
   - Serve como descrição completa do problema
   - Não existe mais campo `mensagem` separado

2. **Anexos:**
   - Salvos em `storage/app/public/chamados/{chamado_id}/`
   - Nome sanitizado com timestamp
   - Vinculados à mensagem específica

3. **Timeline:**
   - Ordenada por `created_at` ASC (mais antiga primeiro)
   - Primeira mensagem é sempre tipo 'abertura'
   - Respostas têm tipo 'resposta'

4. **Autenticação:**
   - Todas as rotas exigem token válido
   - Token enviado via header `Authorization: Bearer {token}`
   - **CRÍTICO:** Se retornar erro 500 ao baixar anexo, verificar se o token está sendo enviado corretamente
   - O erro "Route [login] not defined" significa que o token não está no header

5. **Blob URLs no Frontend:**
   - Necessário para visualizar arquivos protegidos
   - Permite preview de PDFs e imagens
   - Mantém a segurança (anexos não são públicos)
   - **OBRIGATÓRIO:** Usar `fetch()` com header de autenticação, NÃO usar `<a href>` direto

6. **Troubleshooting Download 500:**
   ```javascript
   // ❌ ERRADO - não funciona porque não envia token
   <a href="/api/chamados/anexos/1/download">Download</a>
   
   // ✅ CORRETO - usa fetch com token
   const handleDownload = async () => {
     const response = await fetch('/api/chamados/anexos/1/download', {
       headers: { 'Authorization': `Bearer ${token}` }
     })
     const blob = await response.blob()
     const url = URL.createObjectURL(blob)
     window.open(url, '_blank')
   }
   ```

---

**🎉 Documentação completa da API de Chamados!**
