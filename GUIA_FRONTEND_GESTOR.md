# 👨‍💼 GUIA FRONTEND - GESTOR DO CONTRATO

## 🎯 Funcionalidades do Gestor

O Gestor do Contrato pode:
- ✅ Listar solicitações pendentes de aprovação
- ✅ Ver detalhes da solicitação com anexos
- ✅ Visualizar/Download de cada anexo (PDF)
- ✅ Aprovar anexos individualmente
- ✅ Recusar anexos (com motivo obrigatório)

---

## 📡 ENDPOINTS

### 1. Listar Solicitações Pendentes

**Endpoint:**
```
GET /api/gestor/solicitacoes-pendentes
```

**Headers:**
```javascript
{
  Authorization: `Bearer ${token}`
}
```

**Response (200):**
```json
{
  "solicitacoes": [
    {
      "id": 1,
      "numero": "SP-2024-000001",
      "valor": 12000.00,
      "status": "aguardando_aprovacao_anexos",
      "data": "02/05/2024 10:30",
      "solicitante": "João Silva",
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

---

### 2. Ver Detalhes da Solicitação

**Endpoint:**
```
GET /api/gestor/solicitacoes/{id}
```

**Headers:**
```javascript
{
  Authorization: `Bearer ${token}`
}
```

**Response (200):**
```json
{
  "solicitacao": {
    "id": 1,
    "numero": "SP-2024-000001",
    "valor": 12000.00,
    "status": "Aguardando Aprovação dos Anexos",
    "data": "02/05/2024 10:30",
    "solicitante": "João Silva",
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
      "tipo_anexo_label": "Documento Fiscal (NF, Recibo, Guias, Faturas, etc.)",
      "arquivo_path": "/storage/anexos/demo/documento_fiscal.pdf",
      "arquivo_nome": "documento_fiscal.pdf",
      "status": "Aguardando Aprovação",
      "motivo_recusa": null,
      "aprovado_por": null,
      "data_aprovacao": null,
      "data_envio": "25/01/2024"
    },
    {
      "id": 2,
      "tipo_anexo": "certidao_negativa_debitos",
      "tipo_anexo_label": "Certidão Negativa de Débitos",
      "arquivo_path": "/storage/anexos/demo/certidao.pdf",
      "arquivo_nome": "certidao.pdf",
      "status": "Aguardando Aprovação",
      "motivo_recusa": null,
      "aprovado_por": null,
      "data_aprovacao": null,
      "data_envio": "25/01/2024"
    }
  ]
}
```

---

### 3. Visualizar/Download Anexo

**Endpoint:**
```
GET /api/solicitacoes/{solicitacaoId}/anexos/{anexoId}/download
```

**Headers:**
```javascript
{
  Authorization: `Bearer ${token}`
}
```

**Response:**
- Arquivo PDF (binário)
- Abre em nova aba ou faz download

**Como usar no Frontend:**
```javascript
const handleVisualizar = (solicitacaoId, anexoId) => {
  const token = localStorage.getItem('token')
  const url = `http://localhost:3333/api/solicitacoes/${solicitacaoId}/anexos/${anexoId}/download`
  
  // Método 1: Abrir em nova aba
  window.open(url, '_blank')
  
  // Método 2: Fetch + Blob (para preview inline)
  fetch(url, {
    headers: { Authorization: `Bearer ${token}` }
  })
  .then(res => res.blob())
  .then(blob => {
    const blobUrl = URL.createObjectURL(blob)
    window.open(blobUrl, '_blank')
  })
}
```

---

### 4. Aprovar Anexo

**Endpoint:**
```
POST /api/anexos/{anexoId}/aprovar
```

**Headers:**
```javascript
{
  Authorization: `Bearer ${token}`
}
```

**Response (200):**
```json
{
  "message": "Anexo aprovado com sucesso"
}
```

**Response (403):**
```json
{
  "message": "Apenas gestores de contrato podem aprovar anexos"
}
```

**Response (400):**
```json
{
  "message": "Anexo não está aguardando aprovação"
}
```

---

### 5. Recusar Anexo

**Endpoint:**
```
POST /api/anexos/{anexoId}/recusar
```

**Headers:**
```javascript
{
  Authorization: `Bearer ${token}`,
  'Content-Type': 'application/json'
}
```

**Body:**
```json
{
  "motivo": "Certidão vencida. Por favor, envie uma certidão atualizada."
}
```

**Validações:**
- `motivo`: obrigatório, mínimo 10 caracteres, máximo 500

**Response (200):**
```json
{
  "message": "Anexo recusado com sucesso"
}
```

**Response (422):**
```json
{
  "message": "Os dados fornecidos são inválidos",
  "errors": {
    "motivo": [
      "O campo motivo é obrigatório",
      "O campo motivo deve ter pelo menos 10 caracteres"
    ]
  }
}
```

---

## 💻 EXEMPLO DE CÓDIGO REACT/NEXT.JS

### Página: Lista de Solicitações Pendentes

```typescript
'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'

interface Solicitacao {
  id: number
  numero: string
  valor: number
  status: string
  data: string
  solicitante: string
  fornecedor: string
  empenho: string
  contrato: string
  documento_fiscal: string
  total_anexos: number
  anexos_aprovados: number
  anexos_pendentes: number
  anexos_recusados: number
}

export default function SolicitacoesPendentesPage() {
  const router = useRouter()
  const [solicitacoes, setSolicitacoes] = useState<Solicitacao[]>([])
  const [loading, setLoading] = useState(true)

  const carregarSolicitacoes = async () => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(
        'http://localhost:3333/api/gestor/solicitacoes-pendentes',
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      )

      if (!response.ok) throw new Error('Erro ao carregar solicitações')

      const data = await response.json()
      setSolicitacoes(data.solicitacoes)
    } catch (error) {
      console.error('Erro:', error)
      alert('Erro ao carregar solicitações')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    carregarSolicitacoes()
  }, [])

  if (loading) return <div>Carregando...</div>

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-6">Solicitações Pendentes de Aprovação</h1>

      {solicitacoes.length === 0 && (
        <p className="text-gray-500">Nenhuma solicitação pendente</p>
      )}

      <div className="space-y-4">
        {solicitacoes.map((sol) => (
          <div key={sol.id} className="border rounded-lg p-4 bg-white shadow">
            <div className="flex justify-between items-start">
              <div>
                <h3 className="font-semibold text-lg">{sol.numero}</h3>
                <p className="text-sm text-gray-600">
                  Fornecedor: {sol.fornecedor}
                </p>
                <p className="text-sm text-gray-600">
                  Solicitante: {sol.solicitante}
                </p>
                <p className="text-sm text-gray-600">
                  Empenho: {sol.empenho} | Contrato: {sol.contrato}
                </p>
                <p className="text-sm font-medium mt-2">
                  Valor: R$ {sol.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                </p>
              </div>

              <div className="text-right">
                <div className="bg-blue-100 text-blue-800 px-3 py-1 rounded text-sm mb-2">
                  {sol.status}
                </div>
                <p className="text-xs text-gray-500">{sol.data}</p>
                <div className="mt-2 space-y-1 text-sm">
                  <p className="text-green-600">✅ Aprovados: {sol.anexos_aprovados}</p>
                  <p className="text-yellow-600">⏳ Pendentes: {sol.anexos_pendentes}</p>
                  <p className="text-red-600">❌ Recusados: {sol.anexos_recusados}</p>
                </div>
              </div>
            </div>

            <button
              onClick={() => router.push(`/gestor/solicitacoes/${sol.id}`)}
              className="mt-4 w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600"
            >
              Visualizar Anexos
            </button>
          </div>
        ))}
      </div>
    </div>
  )
}
```

---

### Página: Detalhes e Aprovação de Anexos

```typescript
'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'

interface Anexo {
  id: number
  tipo_anexo: string
  tipo_anexo_label: string
  arquivo_path: string
  arquivo_nome: string
  status: string
  motivo_recusa: string | null
  aprovado_por: string | null
  data_aprovacao: string | null
  data_envio: string
}

interface SolicitacaoDetalhes {
  id: number
  numero: string
  valor: number
  status: string
  data: string
  solicitante: string
  fornecedor: string
  cnpj: string
  empenho: string
  contrato: string
  documento_fiscal: {
    tipo: string
    numero: string
    serie: string
    data_emissao: string
  }
}

export default function AvaliarAnexosPage({ params }: { params: { id: string } }) {
  const router = useRouter()
  const [solicitacao, setSolicitacao] = useState<SolicitacaoDetalhes | null>(null)
  const [anexos, setAnexos] = useState<Anexo[]>([])
  const [loading, setLoading] = useState(true)
  const [modalRecusa, setModalRecusa] = useState<number | null>(null)
  const [motivo, setMotivo] = useState('')

  const carregarDetalhes = async () => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(
        `http://localhost:3333/api/gestor/solicitacoes/${params.id}`,
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      )

      if (!response.ok) throw new Error('Erro ao carregar detalhes')

      const data = await response.json()
      setSolicitacao(data.solicitacao)
      setAnexos(data.anexos)
    } catch (error) {
      console.error('Erro:', error)
      alert('Erro ao carregar detalhes')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    carregarDetalhes()
  }, [params.id])

  const handleVisualizar = (anexoId: number) => {
    const url = `http://localhost:3333/api/solicitacoes/${params.id}/anexos/${anexoId}/download`
    window.open(url, '_blank')
  }

  const handleAprovar = async (anexoId: number) => {
    if (!confirm('Deseja aprovar este anexo?')) return

    try {
      const token = localStorage.getItem('token')
      const response = await fetch(
        `http://localhost:3333/api/anexos/${anexoId}/aprovar`,
        {
          method: 'POST',
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      )

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Erro ao aprovar anexo')
      }

      alert('Anexo aprovado com sucesso!')
      carregarDetalhes() // Recarregar
    } catch (error: any) {
      console.error('Erro:', error)
      alert(error.message || 'Erro ao aprovar anexo')
    }
  }

  const handleRecusar = async (anexoId: number) => {
    if (motivo.trim().length < 10) {
      alert('O motivo deve ter pelo menos 10 caracteres')
      return
    }

    try {
      const token = localStorage.getItem('token')
      const response = await fetch(
        `http://localhost:3333/api/anexos/${anexoId}/recusar`,
        {
          method: 'POST',
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ motivo }),
        }
      )

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Erro ao recusar anexo')
      }

      alert('Anexo recusado com sucesso!')
      setModalRecusa(null)
      setMotivo('')
      carregarDetalhes()
    } catch (error: any) {
      console.error('Erro:', error)
      alert(error.message || 'Erro ao recusar anexo')
    }
  }

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Aguardando Aprovação': return 'bg-yellow-100 text-yellow-800'
      case 'Aprovado': return 'bg-green-100 text-green-800'
      case 'Recusado': return 'bg-red-100 text-red-800'
      default: return 'bg-gray-100 text-gray-800'
    }
  }

  if (loading) return <div>Carregando...</div>

  return (
    <div className="p-6">
      {/* Informações da Solicitação */}
      <div className="bg-white rounded-lg shadow p-6 mb-6">
        <h1 className="text-2xl font-bold mb-4">
          Avaliar Anexos - {solicitacao?.numero}
        </h1>
        
        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <p><strong>Fornecedor:</strong> {solicitacao?.fornecedor}</p>
            <p><strong>CNPJ:</strong> {solicitacao?.cnpj}</p>
            <p><strong>Solicitante:</strong> {solicitacao?.solicitante}</p>
          </div>
          <div>
            <p><strong>Empenho:</strong> {solicitacao?.empenho}</p>
            <p><strong>Contrato:</strong> {solicitacao?.contrato}</p>
            <p><strong>Valor:</strong> R$ {solicitacao?.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
          </div>
        </div>

        <div className="mt-4 p-3 bg-gray-50 rounded">
          <p className="font-semibold">Documento Fiscal:</p>
          <p className="text-sm">
            {solicitacao?.documento_fiscal.tipo} nº {solicitacao?.documento_fiscal.numero}
            {solicitacao?.documento_fiscal.serie && ` - Série ${solicitacao.documento_fiscal.serie}`}
          </p>
          <p className="text-sm text-gray-600">
            Data de Emissão: {solicitacao?.documento_fiscal.data_emissao}
          </p>
        </div>
      </div>

      {/* Lista de Anexos */}
      <h2 className="text-xl font-bold mb-4">Anexos para Aprovação</h2>

      <div className="space-y-4">
        {anexos.map((anexo) => (
          <div key={anexo.id} className="bg-white rounded-lg shadow p-4">
            <div className="flex justify-between items-start">
              <div className="flex-1">
                <h3 className="font-semibold">{anexo.tipo_anexo_label}</h3>
                <p className="text-sm text-gray-600">Arquivo: {anexo.arquivo_nome}</p>
                <p className="text-sm text-gray-600">Data de Envio: {anexo.data_envio}</p>
                
                <div className="mt-2">
                  <span className={`px-3 py-1 rounded text-sm ${getStatusColor(anexo.status)}`}>
                    {anexo.status}
                  </span>
                </div>

                {anexo.motivo_recusa && (
                  <div className="mt-3 p-3 bg-red-50 border border-red-200 rounded">
                    <p className="text-sm font-semibold text-red-800">Motivo da Recusa:</p>
                    <p className="text-sm text-red-700">{anexo.motivo_recusa}</p>
                  </div>
                )}

                {anexo.status === 'Aprovado' && anexo.aprovado_por && (
                  <p className="text-sm text-green-600 mt-2">
                    ✅ Aprovado por {anexo.aprovado_por} em {anexo.data_aprovacao}
                  </p>
                )}
              </div>

              <div className="flex gap-2">
                {/* Botão Visualizar */}
                <button
                  onClick={() => handleVisualizar(anexo.id)}
                  className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                >
                  Visualizar
                </button>

                {/* Botões Aprovar/Recusar (apenas se aguardando aprovação) */}
                {anexo.status === 'Aguardando Aprovação' && (
                  <>
                    <button
                      onClick={() => handleAprovar(anexo.id)}
                      className="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
                    >
                      ✅ Aprovar
                    </button>
                    <button
                      onClick={() => setModalRecusa(anexo.id)}
                      className="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
                    >
                      ❌ Recusar
                    </button>
                  </>
                )}
              </div>
            </div>

            {/* Alerta especial para Documento Fiscal */}
            {anexo.tipo_anexo === 'documento_fiscal' && anexo.status === 'Aguardando Aprovação' && (
              <div className="mt-3 p-3 bg-orange-50 border border-orange-200 rounded">
                <p className="text-sm font-semibold text-orange-800">⚠️ ATENÇÃO:</p>
                <p className="text-sm text-orange-700">
                  Se recusar o Documento Fiscal, o Responsável Técnico deverá <strong>cancelar esta solicitação</strong> e criar uma nova.
                </p>
              </div>
            )}
          </div>
        ))}
      </div>

      {/* Modal de Recusa */}
      {modalRecusa && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 className="text-lg font-bold mb-4">Recusar Anexo</h3>
            
            <label className="block mb-2 text-sm font-medium">
              Motivo da Recusa (mínimo 10 caracteres):
            </label>
            <textarea
              value={motivo}
              onChange={(e) => setMotivo(e.target.value)}
              className="w-full border rounded p-2 mb-4 h-32"
              placeholder="Descreva o motivo da recusa..."
            />

            <div className="flex gap-2 justify-end">
              <button
                onClick={() => {
                  setModalRecusa(null)
                  setMotivo('')
                }}
                className="px-4 py-2 border rounded hover:bg-gray-100"
              >
                Cancelar
              </button>
              <button
                onClick={() => handleRecusar(modalRecusa)}
                className="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
              >
                Confirmar Recusa
              </button>
            </div>
          </div>
        </div>
      )}

      <button
        onClick={() => router.back()}
        className="mt-6 px-6 py-3 border rounded hover:bg-gray-100"
      >
        ← Voltar
      </button>
    </div>
  )
}
```

---

## 🎨 COMPONENTES AUXILIARES

### Badge de Status
```typescript
const StatusBadge = ({ status }: { status: string }) => {
  const colors = {
    'Aguardando Aprovação': 'bg-yellow-100 text-yellow-800',
    'Aprovado': 'bg-green-100 text-green-800',
    'Recusado': 'bg-red-100 text-red-800',
  }

  return (
    <span className={`px-3 py-1 rounded text-sm ${colors[status] || 'bg-gray-100 text-gray-800'}`}>
      {status}
    </span>
  )
}
```

---

## ✅ CHECKLIST DE IMPLEMENTAÇÃO

- [ ] Criar rota `/gestor/solicitacoes-pendentes`
- [ ] Listar solicitações com contadores de anexos
- [ ] Criar rota `/gestor/solicitacoes/{id}`
- [ ] Exibir detalhes da solicitação e fornecedor
- [ ] Listar anexos com status visual
- [ ] Botão "Visualizar" abre PDF em nova aba
- [ ] Botão "Aprovar" (apenas se status aguardando)
- [ ] Botão "Recusar" abre modal (apenas se status aguardando)
- [ ] Modal de recusa com textarea (min 10 chars)
- [ ] Alerta especial para Documento Fiscal
- [ ] Mostrar motivo da recusa (se houver)
- [ ] Recarregar dados após aprovar/recusar
- [ ] Mensagens de sucesso/erro

---

**🎉 Guia completo para implementar o frontend do Gestor!**
