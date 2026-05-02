# 📋 GUIA: Sistema de Suporte - Chamados

## 🎯 VISÃO GERAL

Sistema de chamados de suporte com filtros específicos para **Usuário Comum** e **Gestor de Suporte**.

---

## 📡 ENDPOINT: Listar Chamados

**GET** `/api/chamados`

### Headers
```
Authorization: Bearer {token}
```

---

## 🔍 FILTROS DISPONÍVEIS

### 1. Protocolo (ID do Chamado)
- **Parâmetro:** `protocolo`
- **Tipo:** String
- **Exemplo:** `#3` ou `3`
- **Comportamento:** Busca exata pelo ID do chamado

```typescript
// Exemplo de uso
const params = new URLSearchParams({
  protocolo: '#3'  // ou apenas '3'
})
```

---

### 2. Data de Cadastro
- **Parâmetros:** 
  - `data_cadastro_inicio` (Data Início)
  - `data_cadastro_fim` (Data Fim)
- **Tipo:** String (formato: `YYYY-MM-DD`)
- **Comportamento:** Filtra chamados criados entre as datas

```typescript
const params = new URLSearchParams({
  data_cadastro_inicio: '2026-05-01',
  data_cadastro_fim: '2026-05-31'
})
```

---

### 3. Data de Resposta
- **Parâmetros:** 
  - `data_resposta_inicio` (Data Início)
  - `data_resposta_fim` (Data Fim)
- **Tipo:** String (formato: `YYYY-MM-DD`)
- **Comportamento:** Filtra chamados respondidos entre as datas

```typescript
const params = new URLSearchParams({
  data_resposta_inicio: '2026-05-01',
  data_resposta_fim: '2026-05-31'
})
```

---

### 4. Módulo
- **Parâmetro:** `modulo`
- **Tipo:** String
- **Comportamento:** Busca parcial (LIKE)

```typescript
const params = new URLSearchParams({
  modulo: 'Portal do Fornecedor'
})
```

---

### 5. Usuário Origem (APENAS GESTOR DE SUPORTE)
- **Parâmetro:** `usuario_origem`
- **Tipo:** String
- **Comportamento:** Busca parcial pelo nome do usuário
- **⚠️ IMPORTANTE:** 
  - ✅ **Gestor de Suporte:** Campo ATIVO
  - ❌ **Usuário Comum:** Campo DESABILITADO (sempre filtra pelo próprio usuário logado)

```typescript
// Apenas se o usuário for gestor_suporte
const params = new URLSearchParams({
  usuario_origem: 'João Silva'
})
```

---

### 6. Assunto
- **Parâmetro:** `assunto`
- **Tipo:** String
- **Comportamento:** Busca parcial (LIKE)

```typescript
const params = new URLSearchParams({
  assunto: 'Erro ao enviar'
})
```

---

### 7. Status (Aceita Múltiplos Valores)
- **Parâmetro:** `status`
- **Tipo:** String ou Array
- **Valores aceitos:**
  - `aberto` - Chamado aberto
  - `em_atendimento` - Em atendimento
  - `concluido` - Concluído

**IMPORTANTE:** Para filtrar por múltiplos status, envie separado por vírgula.

```typescript
// Um status apenas
const params = new URLSearchParams({
  status: 'aberto'
})

// Múltiplos status (separados por vírgula)
const params = new URLSearchParams({
  status: 'aberto,em_atendimento'
})

// Ou como array
const status = ['aberto', 'em_atendimento']
const params = new URLSearchParams({
  status: status.join(',')
})
```

---

## 🔀 FILTROS MÚLTIPLOS

### Seleção Múltipla de Status

O filtro de status aceita **múltiplos valores** para retornar chamados com qualquer um dos status selecionados.

#### Formato da URL:
```
/api/chamados?status=aberto,em_atendimento
```

#### Exemplos de Uso:

**Exemplo 1: Chamados Abertos OU Em Atendimento**
```typescript
const params = new URLSearchParams({
  status: 'aberto,em_atendimento'
})

// Retorna: chamados com status 'aberto' OU 'em_atendimento'
```

**Exemplo 2: Todos Exceto Concluídos**
```typescript
const params = new URLSearchParams({
  status: 'aberto,em_atendimento'
})

// Retorna: chamados pendentes (não concluídos)
```

**Exemplo 3: Apenas Concluídos**
```typescript
const params = new URLSearchParams({
  status: 'concluido'
})

// Retorna: apenas chamados concluídos
```

---

## 📤 RESPONSE

```json
{
  "chamados": [
    {
      "id": 3,
      "protocolo": "#3",
      "modulo": "Solicitação de Pagamento",
      "assunto": "Erro ao enviar anexo",
      "usuario": "João Silva (Responsável Técnico)",
      "status": "aberto",
      "data_abertura": "02/05/2026",
      "data_cadastro": "02/05/2026 09:17",
      "data_resposta": null,
      "total_mensagens": 1
    },
    {
      "id": 2,
      "protocolo": "#2",
      "modulo": "Solicitação de Pagamento",
      "assunto": "Erro ao enviar anexo",
      "usuario": "João Silva (Responsável Técnico)",
      "status": "em_atendimento",
      "data_abertura": "02/05/2026",
      "data_cadastro": "02/05/2026 09:17",
      "data_resposta": "02/05/2026 10:30",
      "total_mensagens": 3
    },
    {
      "id": 1,
      "protocolo": "#1",
      "modulo": "Portal do Fornecedor",
      "assunto": "Dúvida sobre anexação de documentos",
      "usuario": "João Silva (Responsável Técnico)",
      "status": "concluido",
      "data_abertura": "30/04/2026",
      "data_cadastro": "30/04/2026 14:20",
      "data_resposta": "30/04/2026 15:45",
      "total_mensagens": 5
    }
  ]
}
```

---

## 💻 IMPLEMENTAÇÃO NO FRONTEND

### Estrutura de Tipos

```typescript
interface Chamado {
  id: number
  protocolo: string
  modulo: string
  assunto: string
  usuario: string
  status: 'aberto' | 'em_atendimento' | 'concluido'
  data_abertura: string
  data_cadastro: string
  data_resposta: string | null
  total_mensagens: number
}

interface FiltrosChamados {
  protocolo?: string
  data_cadastro_inicio?: string
  data_cadastro_fim?: string
  data_resposta_inicio?: string
  data_resposta_fim?: string
  modulo?: string
  usuario_origem?: string  // Apenas para gestor_suporte
  assunto?: string
  status?: string | string[]  // Aceita string única ou array de status
}
```

---

### Exemplo de Componente React

```typescript
'use client'

import { useState, useEffect } from 'react'

export default function ChamadosPage() {
  const [chamados, setChamados] = useState<Chamado[]>([])
  const [filtros, setFiltros] = useState<FiltrosChamados>({})
  const [perfilUsuario, setPerfilUsuario] = useState<string>('') // 'gestor_suporte' ou outro

  const carregarChamados = async () => {
    const token = localStorage.getItem('token')
    
    // Construir query string
    const params = new URLSearchParams()
    
    if (filtros.protocolo) params.append('protocolo', filtros.protocolo)
    if (filtros.data_cadastro_inicio) params.append('data_cadastro_inicio', filtros.data_cadastro_inicio)
    if (filtros.data_cadastro_fim) params.append('data_cadastro_fim', filtros.data_cadastro_fim)
    if (filtros.data_resposta_inicio) params.append('data_resposta_inicio', filtros.data_resposta_inicio)
    if (filtros.data_resposta_fim) params.append('data_resposta_fim', filtros.data_resposta_fim)
    if (filtros.modulo) params.append('modulo', filtros.modulo)
    if (filtros.assunto) params.append('assunto', filtros.assunto)
    
    // Status pode ser string ou array
    if (filtros.status) {
      const statusValue = Array.isArray(filtros.status) 
        ? filtros.status.join(',') 
        : filtros.status
      params.append('status', statusValue)
    }
    
    // Apenas gestor pode filtrar por usuário origem
    if (perfilUsuario === 'gestor_suporte' && filtros.usuario_origem) {
      params.append('usuario_origem', filtros.usuario_origem)
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
    carregarChamados()
  }, [filtros])

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-6">Meus Chamados</h1>

      {/* FILTROS */}
      <div className="bg-white rounded-lg shadow p-6 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
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

          {/* Assunto */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Assunto
            </label>
            <input
              type="text"
              placeholder="Buscar por assunto..."
              className="w-full border rounded px-3 py-2"
              value={filtros.assunto || ''}
              onChange={(e) => setFiltros({ ...filtros, assunto: e.target.value })}
            />
          </div>

          {/* Status (com suporte a múltipla seleção) */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Status
            </label>
            <select
              multiple
              className="w-full border rounded px-3 py-2"
              value={Array.isArray(filtros.status) ? filtros.status : (filtros.status ? [filtros.status] : [])}
              onChange={(e) => {
                const selectedOptions = Array.from(e.target.selectedOptions, option => option.value)
                setFiltros({ ...filtros, status: selectedOptions.length > 0 ? selectedOptions : undefined })
              }}
            >
              <option value="aberto">Aberto</option>
              <option value="em_atendimento">Em Atendimento</option>
              <option value="concluido">Concluído</option>
            </select>
            <p className="text-xs text-gray-500 mt-1">
              Segure Ctrl (Windows) ou Cmd (Mac) para selecionar múltiplos
            </p>
          </div>

          {/* Data Início */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Data Início
            </label>
            <input
              type="date"
              className="w-full border rounded px-3 py-2"
              value={filtros.data_cadastro_inicio || ''}
              onChange={(e) => setFiltros({ ...filtros, data_cadastro_inicio: e.target.value })}
            />
          </div>

          {/* Data Fim */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Data Fim
            </label>
            <input
              type="date"
              className="w-full border rounded px-3 py-2"
              value={filtros.data_cadastro_fim || ''}
              onChange={(e) => setFiltros({ ...filtros, data_cadastro_fim: e.target.value })}
            />
          </div>

          {/* Usuário Origem (APENAS GESTOR) */}
          {perfilUsuario === 'gestor_suporte' && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Usuário Origem
              </label>
              <input
                type="text"
                placeholder="Buscar por usuário..."
                className="w-full border rounded px-3 py-2"
                value={filtros.usuario_origem || ''}
                onChange={(e) => setFiltros({ ...filtros, usuario_origem: e.target.value })}
              />
            </div>
          )}
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
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Módulo
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Assunto
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Data Abertura
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
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {chamado.modulo}
                </td>
                <td className="px-6 py-4 text-sm text-gray-900">
                  {chamado.assunto}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {chamado.data_abertura}
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <span className={`
                    px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                    ${chamado.status === 'aberto' ? 'bg-yellow-100 text-yellow-800' : ''}
                    ${chamado.status === 'em_atendimento' ? 'bg-blue-100 text-blue-800' : ''}
                    ${chamado.status === 'concluido' ? 'bg-green-100 text-green-800' : ''}
                  `}>
                    {chamado.status === 'aberto' ? 'Aberto' : ''}
                    {chamado.status === 'em_atendimento' ? 'Em Atendimento' : ''}
                    {chamado.status === 'concluido' ? 'Concluído' : ''}
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

## 🔒 REGRAS DE PERMISSÃO

### Usuário Comum
- ✅ Vê **APENAS** seus próprios chamados
- ❌ Campo "Usuário Origem" **DESABILITADO** no frontend
- ✅ Pode filtrar por: protocolo, data, módulo, assunto, status

### Gestor de Suporte
- ✅ Vê **TODOS** os chamados do sistema
- ✅ Campo "Usuário Origem" **HABILITADO**
- ✅ Pode filtrar por: protocolo, data, módulo, assunto, status, usuário origem

---

## 📊 CAMPOS DA TABELA

| Campo | Descrição | Fonte |
|-------|-----------|-------|
| Protocolo | ID do chamado (#1, #2, #3...) | `protocolo` |
| Módulo | Módulo indicado | `modulo` |
| Assunto | Título do chamado | `assunto` |
| Data Abertura | Data de criação | `data_abertura` |
| Status | Situação atual | `status` |
| Ações | Botão "Ver Detalhes" | - |

---

## 🎨 CORES PARA STATUS

```typescript
const getStatusColor = (status: string) => {
  switch (status) {
    case 'aberto':
      return 'bg-yellow-100 text-yellow-800'
    case 'em_atendimento':
      return 'bg-blue-100 text-blue-800'
    case 'concluido':
      return 'bg-green-100 text-green-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'aberto':
      return 'Aberto'
    case 'em_atendimento':
      return 'Em Atendimento'
    case 'concluido':
      return 'Concluído'
    default:
      return status
  }
}
```

---

## ✅ CHECKLIST DE IMPLEMENTAÇÃO

- [ ] Criar interface TypeScript para Chamado
- [ ] Criar interface TypeScript para FiltrosChamados
- [ ] Implementar formulário de filtros
- [ ] **IMPORTANTE:** Desabilitar campo "Usuário Origem" para usuários comuns
- [ ] Implementar tabela de resultados
- [ ] Adicionar badges coloridos para status
- [ ] Implementar botão "Ver Detalhes"
- [ ] Implementar botão "Limpar Filtros"
- [ ] Testar filtros combinados
- [ ] Testar permissões (usuário comum vs gestor)

---

**🎉 Backend completo com todos os filtros implementados!**
