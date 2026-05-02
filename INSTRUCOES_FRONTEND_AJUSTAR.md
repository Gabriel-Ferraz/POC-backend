# 🔧 Instruções para o Frontend Ajustar

## Problema Atual

Ao clicar para baixar anexo de chamado, está retornando **erro 500**.

**Causa:** O token de autenticação não está sendo enviado no header da requisição.

---

## ✅ O Que Ajustar no Frontend

### 1. Download de Anexo

**Onde está o problema:**
- Provavelmente está usando `<a href="/api/chamados/anexos/{id}/download">` direto
- Ou fazendo `fetch()` sem o header de autenticação

**Como deve ser:**

```javascript
const downloadAnexo = async (anexoId, nomeArquivo) => {
  const token = localStorage.getItem('token') // ou de onde você pega o token
  
  const response = await fetch(`http://localhost:3333/api/chamados/anexos/${anexoId}/download`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  })
  
  if (!response.ok) {
    console.error('Erro ao baixar')
    return
  }
  
  const blob = await response.blob()
  const blobUrl = URL.createObjectURL(blob)
  window.open(blobUrl, '_blank')
}
```

---

### 2. Como Usar no Componente

**Ao renderizar os anexos da timeline:**

```jsx
{mensagem.anexos.map(anexo => (
  <button 
    key={anexo.id}
    onClick={() => downloadAnexo(anexo.id, anexo.nome)}
  >
    {anexo.nome} ({anexo.tamanho})
  </button>
))}
```

**❌ NÃO use:**
```jsx
<a href={anexo.arquivo_path}>Download</a>
```

---

## 🔍 Como Verificar se Está Funcionando

1. Abra DevTools (F12)
2. Vá em **Network**
3. Clique para baixar um anexo
4. Procure a requisição `/api/chamados/anexos/{id}/download`
5. Veja a aba **Headers** → **Request Headers**
6. **Deve ter:** `Authorization: Bearer {seu-token-aqui}`

Se não aparecer o header `Authorization`, o token não está sendo enviado.

---

## 📦 Estrutura da Response da API

### GET `/api/chamados/{id}` retorna:

```json
{
  "chamado": {
    "id": 3,
    "protocolo": "#3",
    "modulo": "Portal do Fornecedor",
    "assunto": "Erro ao acessar...",
    ...
  },
  "timeline": [
    {
      "id": 1,
      "tipo": "abertura",
      "usuario": "João Silva",
      "mensagem": "Erro ao acessar...",
      "data": "02/05/2026 14:16",
      "anexos": [
        {
          "id": 1,
          "nome": "screenshot.png",
          "tamanho": "33.6 KB",
          "arquivo_path": "/api/chamados/anexos/1/download"
        }
      ]
    },
    {
      "id": 2,
      "tipo": "resposta",
      "usuario": "Ana Paula",
      "mensagem": "Estamos analisando...",
      "data": "02/05/2026 15:30",
      "anexos": []
    }
  ]
}
```

### O campo `arquivo_path` é apenas o path relativo

Use ele assim:

```javascript
const fullUrl = `http://localhost:3333${anexo.arquivo_path}`
// Resulta em: http://localhost:3333/api/chamados/anexos/1/download
```

---

## 🔄 Comparação com Sistema de Solicitações

Se já funciona no sistema de solicitações, **use o mesmo código**, apenas mudando a URL:

```javascript
// Solicitações (já funciona)
fetch(`/api/solicitacoes/${id}/anexos/${anexoId}/download`, {
  headers: { 'Authorization': `Bearer ${token}` }
})

// Chamados (implementar igual)
fetch(`/api/chamados/anexos/${anexoId}/download`, {
  headers: { 'Authorization': `Bearer ${token}` }
})
```

---

## 📝 Resumo

1. ✅ Use `fetch()` com header `Authorization: Bearer ${token}`
2. ✅ Converta resposta para `blob()`
3. ✅ Crie Blob URL com `URL.createObjectURL(blob)`
4. ✅ Abra com `window.open(blobUrl, '_blank')`
5. ❌ Não use `<a href>` direto
6. ❌ Não esqueça o token no header

---

**Ver exemplo completo em:** [`EXEMPLO_FRONTEND_DOWNLOAD_ANEXO.md`](./EXEMPLO_FRONTEND_DOWNLOAD_ANEXO.md)

**Ver documentação da API em:** [`DOCUMENTACAO_API_CHAMADOS.md`](./DOCUMENTACAO_API_CHAMADOS.md)
