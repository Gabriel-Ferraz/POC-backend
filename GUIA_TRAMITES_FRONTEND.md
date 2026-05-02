# 📋 GUIA: TRÂMITES COM ORIGEM/DESTINO/MOTIVO

## ✅ BACKEND ATUALIZADO

**Status:** ✅ IMPLEMENTADO E TESTADO

O backend agora retorna nos trâmites os campos:
- **origem** (VARCHAR 255, NULL): De onde veio a tramitação
- **destino** (VARCHAR 255, NULL): Para onde vai a tramitação
- **motivo** (TEXT, NULL): Motivo/justificativa da tramitação (usado principalmente em recusas/cancelamentos)
- **observacao** (TEXT, NULL): Observação adicional

---

## 📡 RESPONSE DOS ENDPOINTS

### GET /api/solicitacoes/{id}

**Response completo (trâmites inclusos):**

```json
{
  "solicitacao": {
    "id": 2,
    "numero": "SP-2023-000125",
    "status": "pagamento_realizado",
    "status_label": "Pagamento Realizado",
    "valor": "15000.00",
    "created_at": "02/05/2026",
    
    "tramites": [
      {
        "id": 4,
        "fase": "Solicitação Criada",
        "created_at": "02/05/2026 11:17",
        "usuario": {
          "id": 1,
          "name": "João Silva (Responsável Técnico)"
        },
        "origem": "Responsável Técnico",
        "destino": "Sistema",
        "motivo": null,
        "observacao": "Solicitação de pagamento criada"
      },
      {
        "id": 5,
        "fase": "Anexos Aprovados",
        "created_at": "02/05/2026 11:17",
        "usuario": {
          "id": 2,
          "name": "Maria Santos (Gestor Contrato)"
        },
        "origem": "Gestor do Contrato",
        "destino": "Fiscal do Contrato",
        "motivo": null,
        "observacao": "Todos os anexos foram aprovados"
      },
      {
        "id": 6,
        "fase": "Pagamento Realizado",
        "created_at": "02/05/2026 11:17",
        "usuario": {
          "id": 3,
          "name": "Carlos Oliveira (Operador PMSJP)"
        },
        "origem": "Remessa Bancária",
        "destino": "Fornecedor",
        "motivo": "Pagamento conforme programação financeira",
        "observacao": "Pagamento realizado via transferência bancária"
      }
    ]
  }
}
```

---

### GET /api/solicitacoes/{id}/tramites

**Response (endpoint específico de trâmites):**

```json
{
  "tramites": [
    {
      "id": 1,
      "fase": "Solicitação Criada",
      "usuario": "João Silva (Responsável Técnico)",
      "origem": "Responsável Técnico",
      "destino": "Sistema",
      "motivo": null,
      "observacao": "Solicitação de pagamento criada pelo fornecedor",
      "data": "02/05/2026 09:17:45"
    },
    {
      "id": 2,
      "fase": "Anexos Enviados para Aprovação",
      "usuario": "João Silva (Responsável Técnico)",
      "origem": "Responsável Técnico",
      "destino": "Gestor do Contrato",
      "motivo": null,
      "observacao": "Todos os anexos foram enviados e aguardam aprovação do Gestor do Contrato",
      "data": "02/05/2026 09:17:45"
    }
  ]
}
```

---

## 🎯 QUANDO CADA CAMPO É USADO

### `origem` (sempre preenchido quando possível)
- Identifica de **onde** a solicitação veio
- Exemplos:
  - "Responsável Técnico"
  - "Gestor do Contrato"
  - "Fiscal do Contrato"
  - "Secretário Municipal"
  - "Departamento de ISS"
  - "Remessa Bancária"

### `destino` (sempre preenchido quando possível)
- Identifica para **onde** a solicitação vai
- Exemplos:
  - "Sistema"
  - "Gestor do Contrato"
  - "Fiscal do Contrato"
  - "Departamento de Liquidação"
  - "Secretário Municipal"
  - "Fornecedor"

### `motivo` (preenchido apenas quando relevante)
- Usado principalmente em:
  - ❌ **Recusas de anexos**: "Documento fiscal com data incorreta"
  - ❌ **Cancelamentos**: "Documento fiscal com dados incorretos"
  - ✅ **Aprovações especiais**: "Pagamento conforme programação financeira"
  - ⚠️ **Observações importantes**: "Processo prioritário conforme Ofício 123/2024"

### `observacao` (complementa a informação)
- Informação adicional sobre a tramitação
- Mais descritiva que o motivo
- Exemplos:
  - "Solicitação de pagamento criada pelo fornecedor"
  - "Todos os anexos foram aprovados. Solicitação prossegue no fluxo interno da PMSJP"
  - "Pagamento realizado via transferência bancária"

---

## 💻 EXEMPLO DE IMPLEMENTAÇÃO NO FRONTEND

```typescript
'use client'

import { useState, useEffect } from 'react'

interface Usuario {
  id: number
  name: string
}

interface Tramite {
  id: number
  fase: string
  created_at: string
  usuario: Usuario | null
  origem: string | null
  destino: string | null
  motivo: string | null
  observacao: string | null
}

interface Solicitacao {
  id: number
  numero: string
  status: string
  tramites: Tramite[]
}

export default function TramitesPage({ params }: { params: { id: string } }) {
  const [solicitacao, setSolicitacao] = useState<Solicitacao | null>(null)

  const carregarSolicitacao = async () => {
    const token = localStorage.getItem('token')
    const response = await fetch(
      `http://localhost:3333/api/solicitacoes/${params.id}`,
      {
        headers: { Authorization: `Bearer ${token}` }
      }
    )
    const data = await response.json()
    setSolicitacao(data.solicitacao)
  }

  useEffect(() => {
    carregarSolicitacao()
  }, [params.id])

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-4">
        Trâmites da Solicitação {solicitacao?.numero}
      </h1>

      <div className="space-y-4">
        {solicitacao?.tramites.map((tramite) => (
          <div 
            key={tramite.id} 
            className="border rounded-lg p-4 bg-white shadow hover:shadow-md transition-shadow"
          >
            {/* FASE E DATA */}
            <div className="flex justify-between items-start mb-3">
              <h3 className="font-bold text-lg text-gray-800">
                {tramite.fase}
              </h3>
              <span className="text-sm text-gray-500">
                {tramite.created_at}
              </span>
            </div>

            {/* USUÁRIO */}
            {tramite.usuario && (
              <p className="text-sm text-gray-600 mb-2">
                👤 <span className="font-medium">{tramite.usuario.name}</span>
              </p>
            )}

            {/* FLUXO: ORIGEM → DESTINO */}
            {(tramite.origem || tramite.destino) && (
              <div className="flex items-center gap-2 mb-3 p-2 bg-blue-50 rounded">
                {tramite.origem && (
                  <span className="text-sm font-medium text-blue-700">
                    {tramite.origem}
                  </span>
                )}
                {tramite.origem && tramite.destino && (
                  <span className="text-blue-400">→</span>
                )}
                {tramite.destino && (
                  <span className="text-sm font-medium text-blue-700">
                    {tramite.destino}
                  </span>
                )}
              </div>
            )}

            {/* OBSERVAÇÃO */}
            {tramite.observacao && (
              <p className="text-sm text-gray-700 mb-2">
                📝 {tramite.observacao}
              </p>
            )}

            {/* MOTIVO (destaque vermelho para recusas/cancelamentos) */}
            {tramite.motivo && (
              <div className={`
                mt-3 p-3 rounded border
                ${tramite.fase.includes('Recusado') || tramite.fase.includes('Cancelad') 
                  ? 'bg-red-50 border-red-200' 
                  : 'bg-yellow-50 border-yellow-200'
                }
              `}>
                <p className={`
                  text-sm font-semibold
                  ${tramite.fase.includes('Recusado') || tramite.fase.includes('Cancelad')
                    ? 'text-red-800'
                    : 'text-yellow-800'
                  }
                `}>
                  {tramite.fase.includes('Recusado') || tramite.fase.includes('Cancelad')
                    ? '⚠️ Motivo da Recusa/Cancelamento:'
                    : 'ℹ️ Motivo:'
                  }
                </p>
                <p className={`
                  text-sm mt-1
                  ${tramite.fase.includes('Recusado') || tramite.fase.includes('Cancelad')
                    ? 'text-red-700'
                    : 'text-yellow-700'
                  }
                `}>
                  {tramite.motivo}
                </p>
              </div>
            )}
          </div>
        ))}
      </div>

      {/* MENSAGEM QUANDO NÃO HÁ TRÂMITES */}
      {solicitacao && solicitacao.tramites.length === 0 && (
        <div className="text-center py-8 text-gray-500">
          Nenhum trâmite registrado ainda
        </div>
      )}
    </div>
  )
}
```

---

## 🎨 VARIAÇÕES DE LAYOUT

### Opção 1: Timeline Vertical (Recomendado)

```tsx
<div className="relative pl-8">
  {/* Linha vertical da timeline */}
  <div className="absolute left-2 top-0 bottom-0 w-0.5 bg-gray-300"></div>
  
  {tramites.map((tramite, index) => (
    <div key={tramite.id} className="relative mb-6">
      {/* Bolinha na timeline */}
      <div className="absolute -left-6 w-4 h-4 rounded-full bg-blue-500 border-2 border-white"></div>
      
      {/* Conteúdo do trâmite */}
      <div className="bg-white p-4 rounded-lg shadow">
        <h3 className="font-bold">{tramite.fase}</h3>
        <p className="text-sm text-gray-500">{tramite.created_at}</p>
        
        {/* Origem → Destino */}
        {tramite.origem && tramite.destino && (
          <div className="mt-2 text-sm">
            <span className="text-blue-600">{tramite.origem}</span>
            <span className="mx-2">→</span>
            <span className="text-blue-600">{tramite.destino}</span>
          </div>
        )}
        
        {tramite.observacao && (
          <p className="text-sm text-gray-700 mt-2">{tramite.observacao}</p>
        )}
        
        {tramite.motivo && (
          <div className="mt-3 p-2 bg-red-50 rounded">
            <p className="text-sm text-red-700">{tramite.motivo}</p>
          </div>
        )}
      </div>
    </div>
  ))}
</div>
```

### Opção 2: Cards Compactos

```tsx
<div className="grid gap-4 md:grid-cols-2">
  {tramites.map((tramite) => (
    <div key={tramite.id} className="bg-white border rounded-lg p-4 hover:shadow-lg transition-shadow">
      <div className="flex justify-between items-start">
        <h3 className="font-semibold text-gray-800">{tramite.fase}</h3>
        <span className="text-xs text-gray-500">{tramite.created_at}</span>
      </div>
      
      {tramite.usuario && (
        <p className="text-sm text-gray-600 mt-1">{tramite.usuario.name}</p>
      )}
      
      {tramite.origem && tramite.destino && (
        <div className="mt-2 flex items-center gap-2 text-xs">
          <span className="px-2 py-1 bg-blue-100 text-blue-700 rounded">{tramite.origem}</span>
          <span>→</span>
          <span className="px-2 py-1 bg-green-100 text-green-700 rounded">{tramite.destino}</span>
        </div>
      )}
    </div>
  ))}
</div>
```

---

## 📊 FLUXO COMPLETO DE EXEMPLO

```
1. Solicitação Criada
   Responsável Técnico → Sistema
   "Solicitação de pagamento criada pelo fornecedor"

2. Anexos Enviados para Aprovação
   Responsável Técnico → Gestor do Contrato
   "Todos os anexos foram enviados e aguardam aprovação"

3. Anexo Recusado
   Gestor do Contrato → Responsável Técnico
   Motivo: "Nota fiscal com data de emissão incorreta"

4. Anexos Corrigidos e Reenviados
   Responsável Técnico → Gestor do Contrato
   "Anexos corrigidos conforme solicitação"

5. Todos Anexos Aprovados
   Gestor do Contrato → Fiscal do Contrato
   "Todos os anexos foram aprovados. Solicitação prossegue no fluxo interno"

6. Pagamento Realizado
   Remessa Bancária → Fornecedor
   Motivo: "Pagamento conforme programação financeira"
   "Pagamento realizado via transferência bancária"
```

---

## ✅ CHECKLIST FRONTEND

- [ ] Exibir **origem → destino** em formato visual (badge, setas, timeline)
- [ ] Destacar **motivo** em vermelho quando for recusa/cancelamento
- [ ] Mostrar **observacao** como descrição detalhada
- [ ] Exibir **usuário** que realizou a ação
- [ ] Ordenar trâmites por data (mais recente primeiro ou cronológico)
- [ ] Adicionar ícones visuais para cada tipo de fase
- [ ] Mobile-friendly (cards empilhados em mobile)

---

## 🚀 CAMPOS DISPONÍVEIS

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `id` | Integer | ✅ Sim | ID do trâmite |
| `fase` | String | ✅ Sim | Nome da fase (ex: "Solicitação Criada") |
| `created_at` | String | ✅ Sim | Data/hora formatada (dd/mm/yyyy HH:mm) |
| `usuario` | Object/Null | ❌ Não | Usuário que realizou (id, name) |
| `origem` | String/Null | ❌ Não | De onde veio a tramitação |
| `destino` | String/Null | ❌ Não | Para onde vai a tramitação |
| `motivo` | String/Null | ❌ Não | Motivo (recusa, cancelamento, etc) |
| `observacao` | String/Null | ❌ Não | Observação adicional |

---

**🎉 Backend completo! Pronto para integração com o frontend!**
