# 📥 Exemplo Completo: Download de Anexos no Frontend

## 🎯 Problema

Ao clicar no anexo de um chamado, o frontend está retornando erro **500 Internal Server Error**.

### Causa Raiz

O erro acontece porque o token de autenticação **não está sendo enviado** no header da requisição.

Quando o backend não encontra o token, ele tenta redirecionar para `Route::get('login')` (comportamento padrão do Laravel), mas essa rota não existe em APIs, causando o erro:

```
Route [login] not defined
```

---

## ✅ Solução: Como Implementar Download com Autenticação

### 1. Função Helper para Download

```javascript
// utils/downloadHelper.js ou similar

export const downloadAnexoChamado = async (anexoId, nomeOriginal) => {
  try {
    // 1. Obter token do localStorage (ou de onde você armazena)
    const token = localStorage.getItem('token')
    
    if (!token) {
      throw new Error('Token de autenticação não encontrado')
    }
    
    // 2. Fazer requisição autenticada
    const response = await fetch(
      `${import.meta.env.VITE_API_URL}/api/chamados/anexos/${anexoId}/download`,
      {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/octet-stream, application/json'
        }
      }
    )
    
    // 3. Verificar se a requisição foi bem-sucedida
    if (!response.ok) {
      const contentType = response.headers.get('content-type')
      
      // Se retornou JSON, é um erro estruturado
      if (contentType?.includes('application/json')) {
        const error = await response.json()
        throw new Error(error.message || 'Erro ao baixar anexo')
      }
      
      throw new Error(`Erro ${response.status}: ${response.statusText}`)
    }
    
    // 4. Converter resposta para Blob
    const blob = await response.blob()
    
    // 5. Criar URL temporária do Blob
    const blobUrl = URL.createObjectURL(blob)
    
    // 6. Abrir em nova aba (para visualizar PDF, imagem, etc)
    const newWindow = window.open(blobUrl, '_blank')
    
    // Opcional: Limpar URL após abrir (com delay para dar tempo de carregar)
    setTimeout(() => {
      URL.revokeObjectURL(blobUrl)
    }, 1000)
    
    // Se preferir forçar download ao invés de abrir:
    // const link = document.createElement('a')
    // link.href = blobUrl
    // link.download = nomeOriginal
    // document.body.appendChild(link)
    // link.click()
    // document.body.removeChild(link)
    // URL.revokeObjectURL(blobUrl)
    
  } catch (error) {
    console.error('Erro ao baixar anexo:', error)
    throw error
  }
}
```

---

### 2. Componente React - Lista de Anexos

```jsx
// components/AnexosChamado.jsx

import React from 'react'
import { downloadAnexoChamado } from '../utils/downloadHelper'

export const AnexosChamado = ({ anexos }) => {
  const [downloading, setDownloading] = React.useState(null)
  
  const handleDownload = async (anexo) => {
    try {
      setDownloading(anexo.id)
      await downloadAnexoChamado(anexo.id, anexo.nome)
    } catch (error) {
      alert(`Erro ao baixar anexo: ${error.message}`)
    } finally {
      setDownloading(null)
    }
  }
  
  if (!anexos || anexos.length === 0) {
    return <p className="text-muted">Nenhum anexo</p>
  }
  
  return (
    <div className="anexos-list">
      {anexos.map(anexo => (
        <div key={anexo.id} className="anexo-item">
          <button
            type="button"
            onClick={() => handleDownload(anexo)}
            disabled={downloading === anexo.id}
            className="btn btn-link"
          >
            {downloading === anexo.id ? (
              <>
                <span className="spinner-border spinner-border-sm me-2" />
                Baixando...
              </>
            ) : (
              <>
                <i className="bi bi-paperclip me-2" />
                {anexo.nome} ({anexo.tamanho})
              </>
            )}
          </button>
        </div>
      ))}
    </div>
  )
}
```

---

### 3. Uso na Timeline do Chamado

```jsx
// pages/DetalheChamado.jsx

import React from 'react'
import { AnexosChamado } from '../components/AnexosChamado'

export const DetalheChamado = () => {
  const [chamado, setChamado] = React.useState(null)
  
  // ... código para buscar chamado
  
  return (
    <div>
      <h2>Timeline</h2>
      
      {chamado?.timeline.map(mensagem => (
        <div key={mensagem.id} className="timeline-item">
          <div className="mensagem-header">
            <strong>{mensagem.usuario}</strong>
            <span className="badge">{mensagem.tipo}</span>
            <span className="text-muted">{mensagem.data}</span>
          </div>
          
          <p>{mensagem.mensagem}</p>
          
          {/* Renderizar anexos usando o componente */}
          <AnexosChamado anexos={mensagem.anexos} />
        </div>
      ))}
    </div>
  )
}
```

---

## 🔍 Debug: Como Verificar se o Token está Sendo Enviado

### No DevTools do navegador:

1. Abra **DevTools** (F12)
2. Vá na aba **Network**
3. Clique para baixar um anexo
4. Procure a requisição para `/api/chamados/anexos/{id}/download`
5. Clique nela e veja a aba **Headers**
6. Procure por **Request Headers**
7. Deve ter uma linha:
   ```
   Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
   ```

### Se NÃO aparecer o header `Authorization`:

- ❌ O token não está sendo enviado
- ❌ Verifique se o `localStorage.getItem('token')` retorna algo
- ❌ Verifique se você está usando `fetch()` com o header correto

### Se aparecer o header mas retornar 401:

- Token expirado
- Token inválido
- Usuário deslogado

### Se aparecer o header mas retornar 403:

- Usuário autenticado mas sem permissão
- Tentando baixar anexo de chamado de outro usuário (sendo usuário comum)

---

## 🚀 Diferença entre Este Sistema e o de Solicitações

Ambos usam o mesmo padrão! A única diferença é a URL:

```javascript
// Solicitações de Pagamento
fetch('/api/solicitacoes/{id}/anexos/{anexoId}/download', {
  headers: { 'Authorization': `Bearer ${token}` }
})

// Chamados de Suporte
fetch('/api/chamados/anexos/{id}/download', {
  headers: { 'Authorization': `Bearer ${token}` }
})
```

**Se funciona em solicitações e não funciona em chamados, compare:**

1. Como você está pegando o token em cada caso
2. Como você está montando a URL
3. Se os headers estão idênticos

---

## 📋 Checklist de Implementação

- [ ] Criar função `downloadAnexoChamado()` com autenticação
- [ ] Adicionar header `Authorization: Bearer ${token}`
- [ ] Converter resposta para Blob
- [ ] Criar Blob URL com `URL.createObjectURL()`
- [ ] Abrir em nova aba com `window.open()`
- [ ] Adicionar tratamento de erro
- [ ] Adicionar loading state (opcional)
- [ ] Testar no DevTools se o token está sendo enviado
- [ ] Verificar se funciona com diferentes tipos de arquivo (PDF, imagem, etc)

---

## ⚠️ Erros Comuns

### Erro: "Não foi possível baixar o arquivo"
- Verifique se o arquivo existe no storage do backend
- Rode `php artisan storage:link` no backend

### Erro: "Route [login] not defined"
- Token não está sendo enviado no header
- Implemente conforme exemplo acima

### Erro: "Arquivo corrompido" ou "não abre"
- Verifique se está convertendo para Blob corretamente
- Verifique se não está tentando fazer JSON.parse() da resposta

### Anexo baixa mas fica em branco
- Verifique se o mimetype está correto no banco de dados
- Verifique se o arquivo realmente existe no storage

---

**✅ Seguindo este guia, o download de anexos deve funcionar perfeitamente!**
