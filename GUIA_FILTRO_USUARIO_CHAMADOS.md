# 📋 GUIA: Filtro de Usuário em Chamados

## 🎯 REGRAS DE NEGÓCIO

### Usuário Comum (não é Gestor)
- ✅ Campo **TRAVADO** com o nome do próprio usuário
- ❌ **NÃO pode** selecionar outros usuários
- ✅ Vê **APENAS** seus próprios chamados (filtro automático no backend)

### Gestores (Gestor de Suporte OU Gestor de Contrato)
- ✅ Campo com **SELECT** habilitado
- ✅ **PODE** selecionar outros usuários
- ✅ Vê **TODOS** os chamados (sem filtro, ou filtrado por usuário selecionado)

---

## 📡 ENDPOINTS

### 1. Listar Usuários para o Filtro

**GET** `/api/chamados/usuarios`

#### Headers
```
Authorization: Bearer {token}
```

#### Response para Usuário Comum
```json
{
  "usuario_atual": {
    "id": 1,
    "name": "João Silva (Responsável Técnico)",
    "perfil": "responsavel_tecnico",
    "perfil_label": "Responsável Técnico"
  }
}
```

#### Response para Gestor de Suporte
```json
{
  "usuarios": [
    {
      "id": 1,
      "name": "João Silva (Responsável Técnico)",
      "perfil": "responsavel_tecnico",
      "perfil_label": "Responsável Técnico"
    },
    {
      "id": 2,
      "name": "Maria Santos (Gestor Contrato)",
      "perfil": "gestor_contrato",
      "perfil_label": "Gestor do Contrato"
    },
    {
      "id": 4,
      "name": "Ana Paula (Gestor Suporte)",
      "perfil": "gestor_suporte",
      "perfil_label": "Gestor de Suporte"
    }
  ]
}
```

**IMPORTANTE:** Retorna apenas usuários que já criaram pelo menos um chamado.

---

### 2. Filtrar Chamados por Usuário

**GET** `/api/chamados?usuario_id={id}`

#### Parâmetros

| Parâmetro | Tipo | Obrigatório | Descrição | Disponível Para |
|-----------|------|-------------|-----------|-----------------|
| `usuario_id` | Integer | Não | ID do usuário | **APENAS Gestor de Suporte** |

#### Exemplo para Gestor de Suporte
```bash
# Filtrar chamados do usuário ID 1
GET /api/chamados?usuario_id=1
Authorization: Bearer {token_gestor_suporte}
```

#### Exemplo para Usuário Comum
```bash
# Mesmo que envie usuario_id, será IGNORADO
# Backend filtra automaticamente pelo próprio usuário
GET /api/chamados?usuario_id=999
Authorization: Bearer {token_usuario_comum}

# Resultado: retorna APENAS os chamados do usuário logado (não do ID 999)
```

---

## 💻 IMPLEMENTAÇÃO NO FRONTEND

### Interface TypeScript

```typescript
interface Usuario {
  id: number
  name: string
  perfil: string
  perfil_label: string
}

interface ResponseUsuarios {
  usuarios?: Usuario[]        // Para gestor de suporte
  usuario_atual?: Usuario     // Para usuário comum
}

interface FiltrosChamados {
  protocolo?: string
  modulo?: string
  assunto?: string
  status?: string[]
  usuario_id?: number  // ID do usuário selecionado
  data_cadastro_inicio?: string
  data_cadastro_fim?: string
}
```

---

### Componente React Completo

```typescript
'use client'

import { useState, useEffect } from 'react'

export default function ChamadosPage() {
  const [perfilUsuario, setPerfilUsuario] = useState<string>('')
  const [usuarioAtual, setUsuarioAtual] = useState<Usuario | null>(null)
  const [usuarios, setUsuarios] = useState<Usuario[]>([])
  const [filtros, setFiltros] = useState<FiltrosChamados>({})
  const [chamados, setChamados] = useState<Chamado[]>([])

  // Carregar informações do usuário e lista de usuários
  useEffect(() => {
    const carregarUsuarios = async () => {
      const token = localStorage.getItem('token')
      
      // Buscar dados do usuário atual
      const responseMe = await fetch('http://localhost:3333/api/auth/me', {
        headers: { Authorization: `Bearer ${token}` }
      })
      const dataMe = await responseMe.json()
      setPerfilUsuario(dataMe.user.perfil)

      // Buscar lista de usuários (ou apenas o usuário atual)
      const responseUsuarios = await fetch('http://localhost:3333/api/chamados/usuarios', {
        headers: { Authorization: `Bearer ${token}` }
      })
      const dataUsuarios = await responseUsuarios.json()

      if (dataUsuarios.usuario_atual) {
        // Usuário comum - recebe apenas seus próprios dados
        setUsuarioAtual(dataUsuarios.usuario_atual)
      } else if (dataUsuarios.usuarios) {
        // Gestor de suporte - recebe lista de todos os usuários
        setUsuarios(dataUsuarios.usuarios)
      }
    }

    carregarUsuarios()
  }, [])

  // Carregar chamados
  const carregarChamados = async () => {
    const token = localStorage.getItem('token')
    const params = new URLSearchParams()

    if (filtros.protocolo) params.append('protocolo', filtros.protocolo)
    if (filtros.modulo) params.append('modulo', filtros.modulo)
    if (filtros.assunto) params.append('assunto', filtros.assunto)
    
    if (filtros.status && filtros.status.length > 0) {
      params.append('status', filtros.status.join(','))
    }

    // Filtro de usuário (apenas para gestor de suporte)
    if (perfilUsuario === 'gestor_suporte' && filtros.usuario_id) {
      params.append('usuario_id', filtros.usuario_id.toString())
    }

    const response = await fetch(
      `http://localhost:3333/api/chamados?${params.toString()}`,
      {
        headers: { Authorization: `Bearer ${token}` }
      }
    )

    const data = await response.json()
    setChamados(data.chamados)
  }

  useEffect(() => {
    if (perfilUsuario) {
      carregarChamados()
    }
  }, [filtros, perfilUsuario])

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-6">Meus Chamados</h1>

      {/* FILTROS */}
      <div className="bg-white rounded-lg shadow p-6 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          
          {/* FILTRO DE USUÁRIO */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Usuário
            </label>

            {/* USUÁRIO COMUM: Campo travado */}
            {perfilUsuario !== 'gestor_suporte' && usuarioAtual && (
              <input
                type="text"
                value={usuarioAtual.name}
                disabled
                className="w-full border rounded px-3 py-2 bg-gray-100 text-gray-600 cursor-not-allowed"
                title="Você só pode ver seus próprios chamados"
              />
            )}

            {/* GESTOR DE SUPORTE: Select com todos os usuários */}
            {perfilUsuario === 'gestor_suporte' && (
              <select
                className="w-full border rounded px-3 py-2"
                value={filtros.usuario_id || ''}
                onChange={(e) => setFiltros({ 
                  ...filtros, 
                  usuario_id: e.target.value ? parseInt(e.target.value) : undefined 
                })}
              >
                <option value="">Todos os usuários</option>
                {usuarios.map((usuario) => (
                  <option key={usuario.id} value={usuario.id}>
                    {usuario.name}
                  </option>
                ))}
              </select>
            )}
          </div>

          {/* Protocolo */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Protocolo
            </label>
            <input
              type="text"
              placeholder="Buscar por protocolo..."
              className="w-full border rounded px-3 py-2"
              value={filtros.protocolo || ''}
              onChange={(e) => setFiltros({ ...filtros, protocolo: e.target.value })}
            />
          </div>

          {/* Módulo */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Módulo
            </label>
            <input
              type="text"
              placeholder="Buscar por módulo..."
              className="w-full border rounded px-3 py-2"
              value={filtros.modulo || ''}
              onChange={(e) => setFiltros({ ...filtros, modulo: e.target.value })}
            />
          </div>

          {/* Outros filtros... */}
        </div>

        {/* Botão Limpar Filtros */}
        <div className="mt-4 flex justify-end">
          <button
            onClick={() => setFiltros({})}
            className="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
          >
            Limpar Filtros
          </button>
        </div>
      </div>

      {/* TABELA DE CHAMADOS */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Protocolo
              </th>
              
              {/* Coluna Usuário: só aparece para Gestor de Suporte */}
              {perfilUsuario === 'gestor_suporte' && (
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Usuário
                </th>
              )}
              
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Módulo
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Assunto
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Status
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Ações
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {chamados.map((chamado) => (
              <tr key={chamado.id} className="hover:bg-gray-50">
                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {chamado.protocolo}
                </td>
                
                {/* Coluna Usuário: só aparece para Gestor */}
                {perfilUsuario === 'gestor_suporte' && (
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {chamado.usuario}
                  </td>
                )}
                
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {chamado.modulo}
                </td>
                <td className="px-6 py-4 text-sm text-gray-900">
                  {chamado.assunto}
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <span className={`
                    px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                    ${chamado.status === 'aberto' ? 'bg-yellow-100 text-yellow-800' : ''}
                    ${chamado.status === 'em_atendimento' ? 'bg-blue-100 text-blue-800' : ''}
                    ${chamado.status === 'concluido' ? 'bg-green-100 text-green-800' : ''}
                  `}>
                    {chamado.status_label}
                  </span>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button
                    onClick={() => window.location.href = `/suporte/${chamado.id}`}
                    className="text-blue-600 hover:text-blue-900"
                  >
                    Ver Detalhes
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {/* Mensagem quando não há resultados */}
        {chamados.length === 0 && (
          <div className="text-center py-12">
            <p className="text-gray-500">Nenhum chamado encontrado</p>
          </div>
        )}
      </div>
    </div>
  )
}
```

---

## 🎨 VARIAÇÕES DE LAYOUT

### Opção 1: Input Travado (Usuário Comum)

```tsx
{/* Usuário Comum */}
<div className="relative">
  <input
    type="text"
    value="João Silva (Responsável Técnico)"
    disabled
    className="w-full border rounded px-3 py-2 bg-gray-100 text-gray-600 cursor-not-allowed"
  />
  <div className="absolute right-3 top-3">
    <svg className="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
      <path fillRule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clipRule="evenodd" />
    </svg>
  </div>
</div>
<p className="text-xs text-gray-500 mt-1">
  Você só pode visualizar seus próprios chamados
</p>
```

---

### Opção 2: Select com Avatar (Gestor de Suporte)

```tsx
{/* Gestor de Suporte */}
<select className="w-full border rounded px-3 py-2">
  <option value="">📋 Todos os usuários</option>
  {usuarios.map((usuario) => (
    <option key={usuario.id} value={usuario.id}>
      👤 {usuario.name}
    </option>
  ))}
</select>
<p className="text-xs text-gray-500 mt-1">
  Selecione um usuário para filtrar seus chamados
</p>
```

---

## 🔐 SEGURANÇA

### Backend Valida Permissões

Mesmo que o frontend envie `usuario_id`, o backend valida:

```php
// Se não for gestor de suporte, ignora o filtro de usuario_id
if ($request->filled('usuario_id') && $user->perfil === 'gestor_suporte') {
    $query->where('usuario_id', $request->usuario_id);
}

// E sempre filtra pelo próprio usuário se não for gestor
if ($user->perfil !== 'gestor_suporte') {
    $query->where('usuario_id', $user->id);
}
```

**Resultado:** Usuário comum NUNCA consegue ver chamados de outros, mesmo manipulando a requisição.

---

## 🧪 TESTES

### Teste 1: Usuário Comum - Listar Usuários
```bash
curl -X GET http://localhost:3333/api/chamados/usuarios \
  -H "Authorization: Bearer {token_usuario_comum}"
```

**Esperado:**
```json
{
  "usuario_atual": {
    "id": 1,
    "name": "João Silva (Responsável Técnico)",
    "perfil": "responsavel_tecnico",
    "perfil_label": "Responsável Técnico"
  }
}
```

---

### Teste 2: Gestor de Suporte - Listar Usuários
```bash
curl -X GET http://localhost:3333/api/chamados/usuarios \
  -H "Authorization: Bearer {token_gestor_suporte}"
```

**Esperado:**
```json
{
  "usuarios": [
    { "id": 1, "name": "João Silva (Responsável Técnico)", ... },
    { "id": 2, "name": "Maria Santos (Gestor Contrato)", ... },
    ...
  ]
}
```

---

### Teste 3: Usuário Comum Tenta Filtrar por Outro Usuário
```bash
# Tentar filtrar por usuario_id=2 (outro usuário)
curl -X GET "http://localhost:3333/api/chamados?usuario_id=2" \
  -H "Authorization: Bearer {token_usuario_comum}"
```

**Comportamento:**
- ✅ Backend **IGNORA** o filtro `usuario_id=2`
- ✅ Retorna apenas chamados do próprio usuário logado
- ✅ **NÃO retorna erro**, apenas ignora o parâmetro

---

### Teste 4: Gestor Filtra por Usuário Específico
```bash
curl -X GET "http://localhost:3333/api/chamados?usuario_id=1" \
  -H "Authorization: Bearer {token_gestor_suporte}"
```

**Comportamento:**
- ✅ Retorna apenas chamados do usuário ID 1
- ✅ Gestor consegue ver chamados de outros usuários

---

## 📊 RESUMO DAS REGRAS

| Perfil | Campo Usuário | Pode Selecionar Outros | Vê Chamados de Outros |
|--------|---------------|------------------------|----------------------|
| **Usuário Comum** | ✅ Travado (apenas seu nome) | ❌ Não | ❌ Não |
| **Gestor de Suporte** | ✅ Select habilitado | ✅ Sim | ✅ Sim |

---

## ✅ CHECKLIST DE IMPLEMENTAÇÃO

### Backend
- [x] Criar endpoint `/api/chamados/usuarios`
- [x] Retornar apenas usuário atual para não gestores
- [x] Retornar lista de usuários para gestores
- [x] Aceitar filtro `usuario_id` apenas para gestores
- [x] Ignorar filtro `usuario_id` de usuários comuns
- [x] Adicionar relacionamento `chamados()` no model User

### Frontend
- [ ] Buscar usuários em `/api/chamados/usuarios`
- [ ] Exibir campo travado para usuário comum
- [ ] Exibir select habilitado para gestor
- [ ] Enviar `usuario_id` no filtro (apenas gestor)
- [ ] Mostrar coluna "Usuário" na tabela apenas para gestor
- [ ] Adicionar ícone de cadeado no campo travado
- [ ] Testar permissões

---

**🎉 Backend implementado! Pronto para integração com o frontend!**
