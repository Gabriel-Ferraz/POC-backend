# 🎨 CONFIGURAÇÃO DO FRONTEND

## 📡 URL da API Backend

Configure no `.env` ou `.env.local` do seu projeto Next.js:

```env
# URL da API Backend
NEXT_PUBLIC_API_URL=http://localhost:3333/api

# Ou se preferir
NEXT_PUBLIC_API_BASE_URL=http://localhost:3333
```

---

## 🔌 Exemplo de Configuração Completa

### `.env.local` (Next.js)

```env
# API Backend
NEXT_PUBLIC_API_URL=http://localhost:3333/api
NEXT_PUBLIC_API_BASE_URL=http://localhost:3333

# App Config
NEXT_PUBLIC_APP_NAME=POC São José dos Pinhais
NEXT_PUBLIC_APP_VERSION=1.0.0
```

---

## 📝 Como usar no código

### 1. Criar arquivo `lib/api.ts`:

```typescript
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:3333/api';

export const api = {
  baseURL: API_URL,

  // Helper para fazer requests
  async request(endpoint: string, options: RequestInit = {}) {
    const token = localStorage.getItem('token');
    
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(token && { Authorization: `Bearer ${token}` }),
      ...options.headers,
    };

    const response = await fetch(`${API_URL}${endpoint}`, {
      ...options,
      headers,
    });

    if (!response.ok) {
      const error = await response.json();
      throw error;
    }

    return response.json();
  },

  // Métodos auxiliares
  get(endpoint: string) {
    return this.request(endpoint, { method: 'GET' });
  },

  post(endpoint: string, data: any) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },

  put(endpoint: string, data: any) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  },

  delete(endpoint: string) {
    return this.request(endpoint, { method: 'DELETE' });
  },
};
```

### 2. Exemplo de Login:

```typescript
import { api } from '@/lib/api';

async function handleLogin(cpf: string, password: string) {
  try {
    const response = await api.post('/auth/login', {
      cpf,
      password,
    });

    // Salvar token
    localStorage.setItem('token', response.token);
    localStorage.setItem('user', JSON.stringify(response.user));

    return response;
  } catch (error) {
    console.error('Erro no login:', error);
    throw error;
  }
}
```

### 3. Exemplo de Listar Empenhos:

```typescript
import { api } from '@/lib/api';

async function getEmpenhos() {
  try {
    const response = await api.get('/fornecedor/empenhos');
    return response;
  } catch (error) {
    console.error('Erro ao buscar empenhos:', error);
    throw error;
  }
}
```

### 4. Exemplo de Upload de Anexo:

```typescript
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:3333/api';

async function uploadAnexo(solicitacaoId: number, anexoId: number, file: File) {
  const token = localStorage.getItem('token');
  
  const formData = new FormData();
  formData.append('anexo_id', anexoId.toString());
  formData.append('arquivo', file);

  const response = await fetch(
    `${API_URL}/solicitacoes/${solicitacaoId}/anexos`,
    {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        // NÃO adicionar Content-Type para FormData
      },
      body: formData,
    }
  );

  if (!response.ok) {
    const error = await response.json();
    throw error;
  }

  return response.json();
}
```

---

## 🎯 Endpoints Principais

Todos os endpoints começam com a base: `http://localhost:3333/api`

### Autenticação
- `POST /auth/login` - Login
- `GET /auth/me` - Dados do usuário
- `POST /auth/logout` - Logout

### Portal do Fornecedor
- `GET /fornecedor/empenhos`
- `GET /fornecedor/empenhos/{id}`

### Solicitações
- `GET /empenhos/{id}/solicitacoes`
- `POST /empenhos/{id}/solicitacoes`
- `GET /solicitacoes/{id}`
- `POST /solicitacoes/{id}/cancelar`

### Anexos
- `GET /solicitacoes/{id}/anexos`
- `POST /solicitacoes/{id}/anexos` (FormData)
- `POST /solicitacoes/{id}/anexos/enviar-todos`
- `POST /anexos/{id}/aprovar`
- `POST /anexos/{id}/recusar`
- `GET /anexos/{id}/download`

### Suporte
- `GET /chamados`
- `POST /chamados`
- `GET /chamados/{id}`
- `POST /chamados/{id}/responder`

### Prestação de Contas
- `POST /prestacao-contas/exportar`
- `GET /prestacao-contas/exportacoes`

### Orçamentário
- `GET /orcamentario/leis-atos`
- `POST /orcamentario/leis-atos`
- `GET /orcamentario/alteracoes`
- `POST /orcamentario/alteracoes`

---

## 🔐 Autenticação

Todas as requisições (exceto login) precisam do header:

```typescript
Authorization: Bearer {token}
```

Exemplo com axios:

```typescript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:3333/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Interceptor para adicionar token automaticamente
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor para tratar erros
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expirado - redirecionar para login
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

---

## ✅ CORS Configurado

O backend já está configurado para aceitar requisições de:
- ✅ `http://localhost:3000`
- ✅ `http://localhost:3001`
- ✅ `http://127.0.0.1:3000`

Headers permitidos: **todos** (`*`)
Métodos permitidos: **todos** (`GET, POST, PUT, DELETE, etc`)

---

## 🧪 Teste a Conexão

### Teste rápido no console do navegador:

```javascript
// 1. Login
fetch('http://localhost:3333/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    cpf: '12345678900',
    password: 'senha123'
  })
})
.then(res => res.json())
.then(data => {
  console.log('Token:', data.token);
  localStorage.setItem('token', data.token);
});

// 2. Buscar empenhos (após login)
const token = localStorage.getItem('token');
fetch('http://localhost:3333/api/fornecedor/empenhos', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
.then(res => res.json())
.then(data => console.log('Empenhos:', data));
```

---

## 📊 Formato de Resposta

### Sucesso (200/201):
```json
{
  "message": "Operação realizada com sucesso",
  "data": { ... }
}
```

### Erro de Validação (422):
```json
{
  "message": "Dados inválidos",
  "errors": {
    "campo": ["Mensagem de erro"]
  }
}
```

### Erro de Autorização (401):
```json
{
  "message": "Não autorizado"
}
```

---

## 🎯 Resumo

**URL da API:** `http://localhost:3333/api`

**Variável de ambiente no Next.js:**
```env
NEXT_PUBLIC_API_URL=http://localhost:3333/api
```

**CORS:** ✅ Já configurado para `http://localhost:3000`

**Autenticação:** Bearer Token no header `Authorization`

---

**🚀 Agora é só integrar o frontend!**
