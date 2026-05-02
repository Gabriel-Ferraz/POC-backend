# 📋 INSTRUÇÕES FRONTEND - Filtro de Usuário em Chamados

## 🎯 REGRA DE NEGÓCIO

### Usuário Comum (responsavel_tecnico, operador_pmsjp, operador_orcamentario)
- Campo **TRAVADO** mostrando apenas seu próprio nome
- Vê **APENAS** seus próprios chamados

### Gestores (gestor_suporte OU gestor_contrato)
- Campo com **SELECT** para escolher usuários
- Vê **TODOS** os chamados
- Pode filtrar por usuário específico

---

## 📡 1. BUSCAR LISTA DE USUÁRIOS

### Endpoint
```
GET /api/chamados/usuarios
```

### Response - Usuário Comum
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

### Response - Gestor
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

**IMPORTANTE:** Retorna apenas usuários que já criaram pelo menos 1 chamado.

---

## 💻 2. CÓDIGO FRONTEND

### Interfaces TypeScript
```typescript
interface Usuario {
  id: number
  name: string
  perfil: string
  perfil_label: string
}

interface ResponseUsuarios {
  usuarios?: Usuario[]        // Para gestores
  usuario_atual?: Usuario     // Para usuário comum
}

interface FiltrosChamados {
  protocolo?: string
  modulo?: string
  assunto?: string
  status?: string[]
  usuario_id?: number         // NOVO
  data_cadastro_inicio?: string
  data_cadastro_fim?: string
  data_resposta_inicio?: string
  data_resposta_fim?: string
}
```

---

### Carregar Usuários
```typescript
const [perfilUsuario, setPerfilUsuario] = useState<string>('')
const [usuarioAtual, setUsuarioAtual] = useState<Usuario | null>(null)
const [usuarios, setUsuarios] = useState<Usuario[]>([])

useEffect(() => {
  const carregarUsuarios = async () => {
    const token = localStorage.getItem('token')
    
    // Buscar perfil do usuário logado
    const responseMe = await fetch('http://localhost:3333/api/auth/me', {
      headers: { Authorization: `Bearer ${token}` }
    })
    const dataMe = await responseMe.json()
    setPerfilUsuario(dataMe.user.perfil)

    // Buscar lista de usuários
    const responseUsuarios = await fetch('http://localhost:3333/api/chamados/usuarios', {
      headers: { Authorization: `Bearer ${token}` }
    })
    const dataUsuarios = await responseUsuarios.json()

    if (dataUsuarios.usuario_atual) {
      // Usuário comum
      setUsuarioAtual(dataUsuarios.usuario_atual)
    } else if (dataUsuarios.usuarios) {
      // Gestor
      setUsuarios(dataUsuarios.usuarios)
    }
  }

  carregarUsuarios()
}, [])
```

---

### Exibir Campo de Filtro
```typescript
const perfisGestores = ['gestor_suporte', 'gestor_contrato']
const isGestor = perfisGestores.includes(perfilUsuario)

{/* Usuário Comum: Campo Travado */}
{!isGestor && usuarioAtual && (
  <div>
    <label className="block text-sm font-medium text-gray-700 mb-2">
      Usuário
    </label>
    <input
      type="text"
      value={usuarioAtual.name}
      disabled
      className="w-full border rounded px-3 py-2 bg-gray-100 text-gray-600 cursor-not-allowed"
      title="Você só pode ver seus próprios chamados"
    />
  </div>
)}

{/* Gestor: Select */}
{isGestor && (
  <div>
    <label className="block text-sm font-medium text-gray-700 mb-2">
      Usuário
    </label>
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
  </div>
)}
```

---

### Enviar Filtro na Requisição
```typescript
const carregarChamados = async () => {
  const token = localStorage.getItem('token')
  const params = new URLSearchParams()

  // Filtros existentes
  if (filtros.protocolo) params.append('protocolo', filtros.protocolo)
  if (filtros.modulo) params.append('modulo', filtros.modulo)
  if (filtros.assunto) params.append('assunto', filtros.assunto)
  
  if (filtros.status && filtros.status.length > 0) {
    params.append('status', filtros.status.join(','))
  }
  
  if (filtros.data_cadastro_inicio) params.append('data_cadastro_inicio', filtros.data_cadastro_inicio)
  if (filtros.data_cadastro_fim) params.append('data_cadastro_fim', filtros.data_cadastro_fim)
  if (filtros.data_resposta_inicio) params.append('data_resposta_inicio', filtros.data_resposta_inicio)
  if (filtros.data_resposta_fim) params.append('data_resposta_fim', filtros.data_resposta_fim)

  // NOVO: Filtro de usuário (apenas para gestores)
  const perfisGestores = ['gestor_suporte', 'gestor_contrato']
  if (perfisGestores.includes(perfilUsuario) && filtros.usuario_id) {
    params.append('usuario_id', filtros.usuario_id.toString())
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

## 🎨 4. TABELA DE CHAMADOS

### Adicionar Coluna "Usuário" (Apenas para Gestores)
```typescript
<thead className="bg-gray-50">
  <tr>
    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
      Protocolo
    </th>
    
    {/* NOVA COLUNA: só aparece para gestores */}
    {perfisGestores.includes(perfilUsuario) && (
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
  </tr>
</thead>

<tbody>
  {chamados.map((chamado) => (
    <tr key={chamado.id}>
      <td className="px-6 py-4">{chamado.protocolo}</td>
      
      {/* NOVA COLUNA: dados do usuário */}
      {perfisGestores.includes(perfilUsuario) && (
        <td className="px-6 py-4 text-sm text-gray-500">
          {chamado.usuario}
        </td>
      )}
      
      <td className="px-6 py-4">{chamado.modulo}</td>
      <td className="px-6 py-4">{chamado.assunto}</td>
      <td className="px-6 py-4">{chamado.status_label}</td>
    </tr>
  ))}
</tbody>
```

---

## 🔐 5. SEGURANÇA

**O backend valida tudo:**
- Usuário comum que enviar `usuario_id` → parâmetro é **IGNORADO**
- Usuário comum sempre vê apenas seus próprios chamados
- Mesmo manipulando a URL/requisição, usuário comum **NUNCA** vê chamados de outros

---

## ✅ CHECKLIST

- [ ] Chamar `/api/chamados/usuarios` ao carregar a página
- [ ] Verificar se `usuario_atual` ou `usuarios` veio na resposta
- [ ] Exibir campo travado se vier `usuario_atual`
- [ ] Exibir select se vier `usuarios`
- [ ] Enviar `usuario_id` apenas se for gestor
- [ ] Adicionar coluna "Usuário" na tabela (apenas para gestores)
- [ ] Testar com usuário comum e com gestor

---

## 🧪 COMO TESTAR

### 1. Login como Usuário Comum
- Campo de filtro deve estar travado com seu nome
- Deve ver apenas seus próprios chamados
- Coluna "Usuário" NÃO deve aparecer na tabela

### 2. Login como Gestor
- Campo de filtro deve ser um select com todos os usuários
- Deve ver todos os chamados
- Pode filtrar por usuário específico
- Coluna "Usuário" DEVE aparecer na tabela

---

**Backend está pronto e testado! 🎉**
