# ✅ CORRIGIDO: Filtro Múltiplo de Status

## 🎯 PROBLEMA

O filtro de status não permitia selecionar múltiplos valores (ex: buscar chamados que estão "aberto" OU "em_atendimento").

---

## ✅ SOLUÇÃO IMPLEMENTADA

O backend agora aceita **múltiplos valores** no parâmetro `status`, separados por vírgula.

---

## 📡 COMO USAR

### Formato da Query String

```
/api/chamados?status=valor1,valor2,valor3
```

---

## 📝 EXEMPLOS DE USO

### Exemplo 1: Apenas Abertos
```bash
GET /api/chamados?status=aberto
```

**Retorna:** Apenas chamados com status "aberto"

---

### Exemplo 2: Abertos OU Em Atendimento
```bash
GET /api/chamados?status=aberto,em_atendimento
```

**Retorna:** Chamados com status "aberto" **OU** "em_atendimento"

---

### Exemplo 3: Todos os Status
```bash
GET /api/chamados?status=aberto,em_atendimento,concluido
```

**Retorna:** Todos os chamados (equivalente a não filtrar)

---

### Exemplo 4: Combinar com Outros Filtros
```bash
GET /api/chamados?status=aberto,em_atendimento&modulo=Solicitação&data_cadastro_inicio=2026-05-01
```

**Retorna:** Chamados abertos ou em atendimento, do módulo "Solicitação", criados a partir de 01/05/2026

---

## 💻 IMPLEMENTAÇÃO NO FRONTEND

### TypeScript - Construindo a Query

```typescript
interface FiltrosChamados {
  protocolo?: string
  modulo?: string
  assunto?: string
  status?: string[]  // Array de status
  data_cadastro_inicio?: string
  data_cadastro_fim?: string
  // ... outros filtros
}

const carregarChamados = async (filtros: FiltrosChamados) => {
  const params = new URLSearchParams()
  
  // Outros filtros...
  if (filtros.protocolo) params.append('protocolo', filtros.protocolo)
  if (filtros.modulo) params.append('modulo', filtros.modulo)
  
  // Status: converte array para string separada por vírgula
  if (filtros.status && filtros.status.length > 0) {
    params.append('status', filtros.status.join(','))
  }
  
  const response = await fetch(
    `http://localhost:3333/api/chamados?${params.toString()}`,
    {
      headers: { Authorization: `Bearer ${token}` }
    }
  )
  
  const data = await response.json()
  return data.chamados
}
```

---

### React - Select Múltiplo

```typescript
'use client'

import { useState } from 'react'

export default function FiltrosChamados() {
  const [statusSelecionados, setStatusSelecionados] = useState<string[]>([])

  return (
    <div>
      <label className="block text-sm font-medium mb-2">
        Status (selecione um ou mais)
      </label>
      
      {/* Opção 1: Select Múltiplo Nativo */}
      <select
        multiple
        className="w-full border rounded px-3 py-2"
        value={statusSelecionados}
        onChange={(e) => {
          const selected = Array.from(e.target.selectedOptions, opt => opt.value)
          setStatusSelecionados(selected)
        }}
      >
        <option value="aberto">Aberto</option>
        <option value="em_atendimento">Em Atendimento</option>
        <option value="concluido">Concluído</option>
      </select>
      <p className="text-xs text-gray-500 mt-1">
        Segure Ctrl (Windows) ou Cmd (Mac) para selecionar múltiplos
      </p>

      {/* Exibir selecionados */}
      <div className="mt-2 flex gap-2">
        {statusSelecionados.map((status) => (
          <span key={status} className="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
            {status}
            <button 
              onClick={() => setStatusSelecionados(prev => prev.filter(s => s !== status))}
              className="ml-2"
            >
              ×
            </button>
          </span>
        ))}
      </div>
    </div>
  )
}
```

---

### React - Checkboxes (Alternativa)

```typescript
'use client'

import { useState } from 'react'

export default function FiltrosCheckbox() {
  const [statusSelecionados, setStatusSelecionados] = useState<string[]>([])

  const toggleStatus = (status: string) => {
    setStatusSelecionados(prev => 
      prev.includes(status)
        ? prev.filter(s => s !== status)
        : [...prev, status]
    )
  }

  return (
    <div>
      <label className="block text-sm font-medium mb-2">Status</label>
      
      <div className="space-y-2">
        <label className="flex items-center">
          <input
            type="checkbox"
            checked={statusSelecionados.includes('aberto')}
            onChange={() => toggleStatus('aberto')}
            className="mr-2"
          />
          Aberto
        </label>

        <label className="flex items-center">
          <input
            type="checkbox"
            checked={statusSelecionados.includes('em_atendimento')}
            onChange={() => toggleStatus('em_atendimento')}
            className="mr-2"
          />
          Em Atendimento
        </label>

        <label className="flex items-center">
          <input
            type="checkbox"
            checked={statusSelecionados.includes('concluido')}
            onChange={() => toggleStatus('concluido')}
            className="mr-2"
          />
          Concluído
        </label>
      </div>

      {/* Badge com status selecionados */}
      {statusSelecionados.length > 0 && (
        <div className="mt-3 p-2 bg-blue-50 rounded">
          <p className="text-xs text-blue-600">
            Filtrando por: {statusSelecionados.join(', ')}
          </p>
        </div>
      )}
    </div>
  )
}
```

---

## 🧪 TESTES

### Teste 1: Um Status Apenas
```bash
curl -X GET "http://localhost:3333/api/chamados?status=aberto" \
  -H "Authorization: Bearer {token}"
```

**Esperado:** Apenas chamados abertos

---

### Teste 2: Dois Status
```bash
curl -X GET "http://localhost:3333/api/chamados?status=aberto,em_atendimento" \
  -H "Authorization: Bearer {token}"
```

**Esperado:** Chamados abertos OU em atendimento

---

### Teste 3: Todos os Status
```bash
curl -X GET "http://localhost:3333/api/chamados?status=aberto,em_atendimento,concluido" \
  -H "Authorization: Bearer {token}"
```

**Esperado:** Todos os chamados

---

### Teste 4: Status + Outros Filtros
```bash
curl -X GET "http://localhost:3333/api/chamados?status=aberto,em_atendimento&modulo=Portal" \
  -H "Authorization: Bearer {token}"
```

**Esperado:** Chamados abertos ou em atendimento do módulo "Portal"

---

## 📊 COMPORTAMENTO

### Query SQL Gerada

**Antes (um status):**
```sql
WHERE status = 'aberto'
```

**Depois (múltiplos status):**
```sql
WHERE status IN ('aberto', 'em_atendimento')
```

---

## ⚙️ LÓGICA IMPLEMENTADA NO BACKEND

```php
// Filtro por Status (aceita múltiplos valores)
if ($request->filled('status')) {
    $status = $request->status;

    // Se for string separada por vírgula, converte para array
    if (is_string($status)) {
        $status = explode(',', $status);
    }

    // Remove espaços em branco
    $status = array_map('trim', (array) $status);

    // Filtra valores vazios
    $status = array_filter($status);

    if (!empty($status)) {
        $query->whereIn('status', $status);
    }
}
```

### Funcionamento:
1. Verifica se o parâmetro `status` foi enviado
2. Se for string com vírgula (ex: "aberto,em_atendimento"), divide em array
3. Remove espaços em branco de cada valor
4. Filtra valores vazios
5. Usa `whereIn()` para buscar múltiplos valores

---

## ✅ CASOS DE USO

### Caso 1: Ver Chamados Pendentes
```typescript
// Status: aberto + em_atendimento
const chamadosPendentes = await buscar({ 
  status: ['aberto', 'em_atendimento'] 
})
```

### Caso 2: Ver Apenas Concluídos
```typescript
const chamadosConcluidos = await buscar({ 
  status: ['concluido'] 
})
```

### Caso 3: Ver Todos (sem filtro)
```typescript
const todosChamados = await buscar({})
// Não envia o parâmetro status
```

---

## 🎨 UI SUGERIDA

```
┌─────────────────────────────────────────┐
│ Status                                  │
├─────────────────────────────────────────┤
│ ☑ Aberto                                │
│ ☑ Em Atendimento                        │
│ ☐ Concluído                             │
└─────────────────────────────────────────┘

Selecionados: Aberto, Em Atendimento
```

---

## 📋 RESUMO

| Item | Antes | Depois |
|------|-------|--------|
| Tipo aceito | String única | String ou Array |
| Query | `?status=aberto` | `?status=aberto,em_atendimento` |
| SQL | `WHERE status = 'aberto'` | `WHERE status IN ('aberto', 'em_atendimento')` |
| Frontend | Select simples | Select múltiplo ou Checkboxes |

---

**🎉 Backend atualizado! Agora aceita múltiplos status no filtro!**
