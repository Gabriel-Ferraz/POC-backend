# 🚫 REGRAS: DOCUMENTO FISCAL RECUSADO

## 🎯 REGRA PRINCIPAL

**Se o Documento Fiscal for recusado:**
- ❌ **NENHUM** anexo pode ser reenviado
- ❌ **NENHUM** anexo pode ser removido
- ⚠️ Responsável Técnico **DEVE** cancelar a solicitação
- ✅ Criar uma **NOVA** solicitação com documento fiscal correto

**Se qualquer OUTRO anexo for recusado (não sendo o Documento Fiscal):**
- ✅ **PODE** reenviar o anexo recusado
- ✅ **PODE** remover e fazer novo upload
- ✅ Após corrigir, envia todos novamente para aprovação

---

## 📡 NOVO RESPONSE DO ENDPOINT

### GET /api/solicitacoes/{solicitacaoId}/anexos

**Response:**
```json
{
  "solicitacao": {
    "id": 5,
    "numero": "SP-2026-000004",
    "status": "Anexos Recusados",
    "documento_fiscal_recusado": true,  // ⬅️ FLAG PRINCIPAL
    "anexos": [
      {
        "id": 16,
        "tipo_anexo": "documento_fiscal",
        "tipo_anexo_label": "Documento Fiscal (NF, Recibo, Guias, Faturas, etc.)",
        "arquivo_path": "/storage/anexos/abc123.pdf",
        "arquivo_nome": "nota_fiscal.pdf",
        "status": "Recusado",
        "data_envio": "2026-05-02",
        "motivo_recusa": "Nota fiscal com data de emissão incorreta",
        "is_documento_fiscal": true,
        "pode_reenviar": false,  // ⬅️ FALSE porque é documento fiscal recusado
        "pode_remover": false
      },
      {
        "id": 17,
        "tipo_anexo": "certidao_negativa_debitos",
        "tipo_anexo_label": "Certidão Negativa de Débitos",
        "arquivo_path": "/storage/anexos/def456.pdf",
        "arquivo_nome": "certidao.pdf",
        "status": "Aguardando Aprovação",
        "data_envio": "2026-05-02",
        "motivo_recusa": null,
        "is_documento_fiscal": false,
        "pode_reenviar": false,  // ⬅️ FALSE porque DOC FISCAL foi recusado
        "pode_remover": false
      },
      {
        "id": 18,
        "tipo_anexo": "certidao_tributaria",
        "tipo_anexo_label": "Certidão Tributária",
        "arquivo_path": "/storage/anexos/ghi789.pdf",
        "arquivo_nome": "certidao_trib.pdf",
        "status": "Recusado",
        "data_envio": "2026-05-02",
        "motivo_recusa": "Certidão vencida",
        "is_documento_fiscal": false,
        "pode_reenviar": false,  // ⬅️ FALSE porque DOC FISCAL foi recusado
        "pode_remover": false
      },
      // ... mais anexos
    ]
  }
}
```

---

## 📊 CENÁRIO 1: Documento Fiscal Recusado

```json
{
  "solicitacao": {
    "documento_fiscal_recusado": true,  // ⬅️ TRUE
    "anexos": [
      {
        "tipo_anexo": "documento_fiscal",
        "status": "Recusado",
        "motivo_recusa": "Nota fiscal inválida",
        "pode_reenviar": false,  // ⬅️ FALSE
        "pode_remover": false
      },
      {
        "tipo_anexo": "certidao_negativa_debitos",
        "status": "Aguardando Aprovação",
        "pode_reenviar": false,  // ⬅️ FALSE (bloqueado pelo doc fiscal)
        "pode_remover": false
      }
    ]
  }
}
```

**Frontend deve:**
- ❌ Desabilitar todos os botões de "Upload/Substituir"
- ❌ Desabilitar todos os botões de "Remover"
- ⚠️ Mostrar alerta: "Documento Fiscal foi recusado. Você deve cancelar esta solicitação e criar uma nova."
- ✅ Mostrar apenas botão "Cancelar Solicitação"

---

## 📊 CENÁRIO 2: Certidão Recusada (Documento Fiscal OK)

```json
{
  "solicitacao": {
    "documento_fiscal_recusado": false,  // ⬅️ FALSE
    "anexos": [
      {
        "tipo_anexo": "documento_fiscal",
        "status": "Aprovado",
        "pode_reenviar": false,  // ⬅️ FALSE (já aprovado)
        "pode_remover": false
      },
      {
        "tipo_anexo": "certidao_negativa_debitos",
        "status": "Recusado",
        "motivo_recusa": "Certidão vencida",
        "pode_reenviar": true,  // ⬅️ TRUE (pode corrigir)
        "pode_remover": true
      }
    ]
  }
}
```

**Frontend deve:**
- ✅ Habilitar botão "Substituir" no anexo recusado
- ✅ Habilitar botão "Remover" no anexo recusado
- ✅ Após corrigir, mostrar botão "Enviar Todos para Aprovação"

---

## 📊 CENÁRIO 3: Todos Pendentes (Primeira vez)

```json
{
  "solicitacao": {
    "documento_fiscal_recusado": false,  // ⬅️ FALSE
    "anexos": [
      {
        "tipo_anexo": "documento_fiscal",
        "status": "Pendente",
        "arquivo_path": null,
        "pode_reenviar": true,  // ⬅️ TRUE (pode fazer upload)
        "pode_remover": false   // FALSE (não tem arquivo ainda)
      },
      {
        "tipo_anexo": "certidao_negativa_debitos",
        "status": "Anexo Cadastrado",
        "arquivo_path": "/storage/anexos/abc.pdf",
        "pode_reenviar": true,  // ⬅️ TRUE (pode substituir)
        "pode_remover": true    // TRUE (tem arquivo e pode remover)
      }
    ]
  }
}
```

**Frontend deve:**
- ✅ Mostrar input file para anexos sem arquivo
- ✅ Mostrar botão "Substituir" para anexos com arquivo
- ✅ Mostrar botão "Remover" para anexos com arquivo

---

## 🔑 LÓGICA DAS FLAGS

### `documento_fiscal_recusado` (FLAG GERAL)
```javascript
// TRUE se o anexo do tipo "documento_fiscal" está com status "recusado"
const documentoFiscalRecusado = anexos
  .find(a => a.tipo_anexo === 'documento_fiscal')
  ?.status === 'Recusado'
```

### `pode_reenviar` (FLAG INDIVIDUAL)
```javascript
const podeReenviar = () => {
  // Se documento fiscal foi recusado, NINGUÉM pode reenviar
  if (documentoFiscalRecusado) return false

  // Se é o próprio documento fiscal recusado, não pode
  if (anexo.tipo_anexo === 'documento_fiscal' && anexo.status === 'Recusado') {
    return false
  }

  // Se é outro anexo recusado (não doc fiscal), PODE
  if (anexo.status === 'Recusado' && anexo.tipo_anexo !== 'documento_fiscal') {
    return true
  }

  // Se está pendente ou anexo_cadastrado, PODE
  if (anexo.status === 'Pendente' || anexo.status === 'Anexo Cadastrado') {
    return true
  }

  // Outros casos (aprovado, aguardando aprovação): NÃO PODE
  return false
}
```

### `pode_remover` (FLAG INDIVIDUAL)
```javascript
const podeRemover = podeReenviar && anexo.arquivo_path !== null
// Só pode remover se pode reenviar E tem arquivo
```

---

## 💻 EXEMPLO DE IMPLEMENTAÇÃO NO FRONTEND

```typescript
'use client'

import { useState, useEffect } from 'react'

interface Anexo {
  id: number
  tipo_anexo: string
  tipo_anexo_label: string
  arquivo_path: string | null
  arquivo_nome: string | null
  status: string
  motivo_recusa: string | null
  is_documento_fiscal: boolean
  pode_reenviar: boolean
  pode_remover: boolean
}

interface Solicitacao {
  id: number
  numero: string
  status: string
  documento_fiscal_recusado: boolean  // ⬅️ NOVA FLAG
  anexos: Anexo[]
}

export default function AnexosPage({ params }: { params: { id: string } }) {
  const [solicitacao, setSolicitacao] = useState<Solicitacao | null>(null)

  const carregarAnexos = async () => {
    const token = localStorage.getItem('token')
    const response = await fetch(
      `http://localhost:3333/api/solicitacoes/${params.id}/anexos`,
      {
        headers: { Authorization: `Bearer ${token}` }
      }
    )
    const data = await response.json()
    setSolicitacao(data.solicitacao)
  }

  useEffect(() => {
    carregarAnexos()
  }, [params.id])

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-4">
        Anexos da Solicitação {solicitacao?.numero}
      </h1>

      {/* ALERTA SE DOCUMENTO FISCAL FOI RECUSADO */}
      {solicitacao?.documento_fiscal_recusado && (
        <div className="bg-red-50 border-2 border-red-500 rounded-lg p-4 mb-6">
          <p className="font-bold text-red-800 mb-2">
            ⚠️ ATENÇÃO: Documento Fiscal foi recusado!
          </p>
          <p className="text-red-700 mb-3">
            Não é possível corrigir o Documento Fiscal. Você deve:
          </p>
          <ol className="list-decimal list-inside text-red-700 mb-3 space-y-1">
            <li>Cancelar esta solicitação</li>
            <li>Criar uma nova solicitação com o documento correto</li>
          </ol>
          <button
            onClick={() => window.location.href = `/solicitacoes/${params.id}/cancelar`}
            className="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
          >
            Cancelar Solicitação
          </button>
        </div>
      )}

      {/* LISTA DE ANEXOS */}
      <div className="space-y-4">
        {solicitacao?.anexos.map((anexo) => (
          <div key={anexo.id} className="border rounded-lg p-4 bg-white shadow">
            <div className="flex justify-between items-start">
              <div className="flex-1">
                <h3 className="font-semibold">
                  {anexo.tipo_anexo_label}
                  {anexo.is_documento_fiscal && (
                    <span className="ml-2 text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded">
                      DOCUMENTO FISCAL
                    </span>
                  )}
                </h3>
                
                <p className="text-sm text-gray-500">Status: {anexo.status}</p>
                
                {anexo.arquivo_nome && (
                  <p className="text-sm text-gray-700">Arquivo: {anexo.arquivo_nome}</p>
                )}

                {/* MOTIVO DA RECUSA */}
                {anexo.motivo_recusa && (
                  <div className="mt-3 p-3 bg-red-50 border border-red-200 rounded">
                    <p className="text-sm font-semibold text-red-800">Motivo da Recusa:</p>
                    <p className="text-sm text-red-700">{anexo.motivo_recusa}</p>
                  </div>
                )}
              </div>

              <div className="flex gap-2">
                {/* BOTÃO DE UPLOAD/SUBSTITUIR */}
                {anexo.pode_reenviar && (
                  <label className="cursor-pointer bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    {anexo.arquivo_path ? 'Substituir' : 'Selecionar PDF'}
                    <input
                      type="file"
                      accept=".pdf"
                      className="hidden"
                      onChange={(e) => {
                        const file = e.target.files?.[0]
                        if (file) handleUpload(anexo.id, file)
                      }}
                    />
                  </label>
                )}

                {/* BOTÃO DE VISUALIZAR */}
                {anexo.arquivo_path && (
                  <button
                    onClick={() => handleVisualizar(anexo.id)}
                    className="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"
                  >
                    Visualizar
                  </button>
                )}

                {/* BOTÃO DE REMOVER */}
                {anexo.pode_remover && (
                  <button
                    onClick={() => handleRemover(anexo.id)}
                    className="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
                  >
                    Remover
                  </button>
                )}

                {/* INDICADOR SE NÃO PODE REENVIAR */}
                {!anexo.pode_reenviar && anexo.arquivo_path && (
                  <span className="text-sm text-gray-500 italic">
                    {anexo.status === 'Aprovado' ? '✅ Aprovado' : '🔒 Bloqueado'}
                  </span>
                )}
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* BOTÃO ENVIAR TODOS */}
      {!solicitacao?.documento_fiscal_recusado && todosEnviados && (
        <button
          onClick={handleEnviarTodos}
          className="mt-6 w-full bg-green-500 text-white py-3 rounded hover:bg-green-600"
        >
          Enviar Todos para Aprovação
        </button>
      )}
    </div>
  )
}
```

---

## ✅ RESUMO DAS REGRAS

| Situação | documento_fiscal_recusado | pode_reenviar | pode_remover | Ação Permitida |
|----------|--------------------------|---------------|--------------|----------------|
| Doc Fiscal recusado | ✅ TRUE | ❌ FALSE | ❌ FALSE | Cancelar solicitação |
| Outros anexos quando Doc Fiscal recusado | ✅ TRUE | ❌ FALSE | ❌ FALSE | Nenhuma |
| Anexo comum recusado (Doc Fiscal OK) | ❌ FALSE | ✅ TRUE | ✅ TRUE | Reenviar/Remover |
| Anexo pendente | ❌ FALSE | ✅ TRUE | ❌ FALSE | Fazer upload |
| Anexo cadastrado | ❌ FALSE | ✅ TRUE | ✅ TRUE | Substituir/Remover |
| Anexo aprovado | ❌ FALSE | ❌ FALSE | ❌ FALSE | Apenas visualizar |
| Anexo aguardando aprovação | ❌ FALSE | ❌ FALSE | ❌ FALSE | Apenas visualizar |

---

## 🎯 CHECKLIST FRONTEND

- [ ] Verificar flag `documento_fiscal_recusado` no response
- [ ] Se TRUE, mostrar alerta vermelho no topo
- [ ] Se TRUE, desabilitar TODOS os botões de upload/remover
- [ ] Se TRUE, mostrar botão "Cancelar Solicitação"
- [ ] Usar flag `pode_reenviar` para mostrar/ocultar botão de upload
- [ ] Usar flag `pode_remover` para mostrar/ocultar botão de remover
- [ ] Mostrar `motivo_recusa` em destaque se existir
- [ ] Adicionar badge "DOCUMENTO FISCAL" se `is_documento_fiscal === true`

---

**🚀 Backend atualizado com as flags! Pronto para integração!**
