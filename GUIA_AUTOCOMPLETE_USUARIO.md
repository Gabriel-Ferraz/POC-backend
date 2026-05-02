# 🔍 GUIA: Autocomplete de Usuário em Chamados

## 🎯 REGRA DE NEGÓCIO

### Usuário Comum
- Campo **TRAVADO** mostrando apenas seu próprio nome
- Vê **APENAS** seus próprios chamados

### Gestores (gestor_suporte OU gestor_contrato)
- Campo **AUTOCOMPLETE** (busca dinâmica)
- Digita e aparece lista de sugestões
- Pode selecionar usuário ou deixar em branco (todos)
- Vê **TODOS** os chamados

---

## 📡 ENDPOINT ATUALIZADO

### Buscar Usuários (com suporte a autocomplete)

**GET** `/api/chamados/usuarios?busca={termo}`

#### Parâmetros

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| `busca` | String | Não | Termo para buscar no nome do usuário |

#### Exemplos

```bash
# Buscar todos os usuários (máximo 20)
GET /api/chamados/usuarios
Authorization: Bearer {token_gestor}

# Buscar usuários com "maria" no nome
GET /api/chamados/usuarios?busca=maria
Authorization: Bearer {token_gestor}

# Buscar usuários com "silva" no nome
GET /api/chamados/usuarios?busca=silva
Authorization: Bearer {token_gestor}
```

#### Response para Usuário Comum
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

#### Response para Gestor (sem busca)
```json
{
  "usuarios": [
    {
      "id": 1,
      "name": "Ana Paula Santos",
      "perfil": "gestor_suporte",
      "perfil_label": "Gestor de Suporte"
    },
    {
      "id": 2,
      "name": "João Silva",
      "perfil": "responsavel_tecnico",
      "perfil_label": "Responsável Técnico"
    },
    {
      "id": 3,
      "name": "Maria Santos Costa",
      "perfil": "gestor_contrato",
      "perfil_label": "Gestor do Contrato"
    }
  ]
}
```

#### Response para Gestor (com busca "maria")
```json
{
  "usuarios": [
    {
      "id": 3,
      "name": "Maria Santos Costa",
      "perfil": "gestor_contrato",
      "perfil_label": "Gestor do Contrato"
    },
    {
      "id": 5,
      "name": "Maria José Oliveira",
      "perfil": "operador_pmsjp",
      "perfil_label": "Operador PMSJP"
    }
  ]
}
```

**IMPORTANTE:**
- Retorna no máximo **20 usuários** por requisição
- Retorna apenas usuários que **já criaram pelo menos 1 chamado**
- Busca é **case-insensitive** (ignora maiúsculas/minúsculas)

---

## 💻 IMPLEMENTAÇÃO NO FRONTEND

### 1. Interfaces TypeScript

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
  usuario_id?: number         // ID do usuário selecionado
  data_cadastro_inicio?: string
  data_cadastro_fim?: string
  data_resposta_inicio?: string
  data_resposta_fim?: string
}
```

---

### 2. Estados do Componente

```typescript
const [perfilUsuario, setPerfilUsuario] = useState<string>('')
const [usuarioAtual, setUsuarioAtual] = useState<Usuario | null>(null)
const [usuarios, setUsuarios] = useState<Usuario[]>([])
const [usuarioSelecionado, setUsuarioSelecionado] = useState<Usuario | null>(null)
const [buscaUsuario, setBuscaUsuario] = useState<string>('')
const [mostrarSugestoes, setMostrarSugestoes] = useState<boolean>(false)
const [filtros, setFiltros] = useState<FiltrosChamados>({})
```

---

### 3. Função para Buscar Usuários (Autocomplete)

```typescript
const buscarUsuarios = async (termo: string) => {
  const token = localStorage.getItem('token')
  const params = new URLSearchParams()
  
  if (termo) {
    params.append('busca', termo)
  }
  
  const response = await fetch(
    `http://localhost:3333/api/chamados/usuarios?${params.toString()}`,
    {
      headers: { Authorization: `Bearer ${token}` }
    }
  )
  
  const data = await response.json()
  
  if (data.usuarios) {
    setUsuarios(data.usuarios)
  }
}

// Debounce para não fazer requisição a cada tecla
const handleBuscaChange = (valor: string) => {
  setBuscaUsuario(valor)
  setMostrarSugestoes(true)
  
  // Limpar seleção se apagar o campo
  if (!valor) {
    setUsuarioSelecionado(null)
    setFiltros({ ...filtros, usuario_id: undefined })
  }
  
  // Debounce de 300ms
  const timer = setTimeout(() => {
    buscarUsuarios(valor)
  }, 300)
  
  return () => clearTimeout(timer)
}
```

---

### 4. Carregar Dados Iniciais

```typescript
useEffect(() => {
  const carregarDados = async () => {
    const token = localStorage.getItem('token')
    
    // Buscar perfil do usuário logado
    const responseMe = await fetch('http://localhost:3333/api/auth/me', {
      headers: { Authorization: `Bearer ${token}` }
    })
    const dataMe = await responseMe.json()
    setPerfilUsuario(dataMe.user.perfil)

    // Buscar usuários/dados do filtro
    const responseUsuarios = await fetch('http://localhost:3333/api/chamados/usuarios', {
      headers: { Authorization: `Bearer ${token}` }
    })
    const dataUsuarios = await responseUsuarios.json()

    if (dataUsuarios.usuario_atual) {
      // Usuário comum - campo travado
      setUsuarioAtual(dataUsuarios.usuario_atual)
    } else if (dataUsuarios.usuarios) {
      // Gestor - carrega lista inicial
      setUsuarios(dataUsuarios.usuarios)
    }
  }

  carregarDados()
}, [])
```

---

### 5. Componente Autocomplete para Gestores

```typescript
const perfisGestores = ['gestor_suporte', 'gestor_contrato']
const isGestor = perfisGestores.includes(perfilUsuario)

{/* USUÁRIO COMUM: Campo Travado */}
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
    <p className="text-xs text-gray-500 mt-1">
      Você só pode ver seus próprios chamados
    </p>
  </div>
)}

{/* GESTOR: Autocomplete */}
{isGestor && (
  <div className="relative">
    <label className="block text-sm font-medium text-gray-700 mb-2">
      Usuário
    </label>
    <input
      type="text"
      value={buscaUsuario}
      onChange={(e) => handleBuscaChange(e.target.value)}
      onFocus={() => setMostrarSugestoes(true)}
      placeholder="Buscar por nome do usuário..."
      className="w-full border rounded px-3 py-2 pr-10"
    />
    
    {/* Ícone de busca */}
    <div className="absolute right-3 top-10 text-gray-400">
      🔍
    </div>
    
    {/* Lista de sugestões */}
    {mostrarSugestoes && usuarios.length > 0 && (
      <div className="absolute z-10 w-full mt-1 bg-white border rounded shadow-lg max-h-60 overflow-y-auto">
        {usuarios.map((usuario) => (
          <div
            key={usuario.id}
            onClick={() => {
              setUsuarioSelecionado(usuario)
              setBuscaUsuario(usuario.name)
              setMostrarSugestoes(false)
              setFiltros({ ...filtros, usuario_id: usuario.id })
            }}
            className="px-4 py-2 hover:bg-gray-100 cursor-pointer"
          >
            <div className="font-medium">{usuario.name}</div>
            <div className="text-xs text-gray-500">{usuario.perfil_label}</div>
          </div>
        ))}
      </div>
    )}
    
    {/* Botão para limpar seleção */}
    {usuarioSelecionado && (
      <button
        onClick={() => {
          setUsuarioSelecionado(null)
          setBuscaUsuario('')
          setFiltros({ ...filtros, usuario_id: undefined })
        }}
        className="mt-2 text-sm text-blue-600 hover:text-blue-800"
      >
        ✕ Limpar filtro (ver todos os usuários)
      </button>
    )}
    
    <p className="text-xs text-gray-500 mt-1">
      Digite para buscar ou deixe em branco para ver todos
    </p>
  </div>
)}

{/* Clique fora fecha sugestões */}
<script>
useEffect(() => {
  const handleClickFora = () => setMostrarSugestoes(false)
  document.addEventListener('click', handleClickFora)
  return () => document.removeEventListener('click', handleClickFora)
}, [])
</script>
```

---

### 6. Enviar Filtro na Requisição

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

  // Filtro de usuário (apenas para gestores)
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

// Recarregar quando filtros mudarem
useEffect(() => {
  if (perfilUsuario) {
    carregarChamados()
  }
}, [filtros, perfilUsuario])
```

---

## 🎨 BIBLIOTECAS SUGERIDAS

### Opção 1: Implementação Manual (código acima)
- Sem dependências extras
- Controle total

### Opção 2: react-select (mais robusta)
```bash
npm install react-select
```

```typescript
import Select from 'react-select'

<Select
  isClearable
  isSearchable
  placeholder="Buscar usuário..."
  noOptionsMessage={() => "Nenhum usuário encontrado"}
  loadingMessage={() => "Buscando..."}
  options={usuarios.map(u => ({ value: u.id, label: u.name }))}
  onInputChange={(valor) => {
    if (valor.length > 2) {
      buscarUsuarios(valor)
    }
  }}
  onChange={(opcao) => {
    setFiltros({ 
      ...filtros, 
      usuario_id: opcao?.value 
    })
  }}
/>
```

### Opção 3: Radix UI Combobox
```bash
npm install @radix-ui/react-popover
```

---

## 🧪 TESTES

### Teste 1: Usuário Comum
```bash
curl -X GET http://localhost:3333/api/chamados/usuarios \
  -H "Authorization: Bearer {token_usuario_comum}"
```

**Esperado:**
```json
{
  "usuario_atual": {
    "id": 1,
    "name": "João Silva",
    ...
  }
}
```

---

### Teste 2: Gestor - Buscar Todos
```bash
curl -X GET http://localhost:3333/api/chamados/usuarios \
  -H "Authorization: Bearer {token_gestor}"
```

**Esperado:** Lista com até 20 usuários

---

### Teste 3: Gestor - Buscar por "maria"
```bash
curl -X GET "http://localhost:3333/api/chamados/usuarios?busca=maria" \
  -H "Authorization: Bearer {token_gestor}"
```

**Esperado:** Apenas usuários com "maria" no nome

---

### Teste 4: Filtrar Chamados por Usuário
```bash
curl -X GET "http://localhost:3333/api/chamados?usuario_id=3" \
  -H "Authorization: Bearer {token_gestor}"
```

**Esperado:** Apenas chamados do usuário ID 3

---

## ✅ CHECKLIST

### Backend
- [x] Endpoint aceita parâmetro `busca`
- [x] Busca case-insensitive no nome
- [x] Limita a 20 resultados
- [x] Retorna apenas usuários com chamados
- [x] Validação de permissão (apenas gestor)

### Frontend
- [ ] Input com autocomplete para gestores
- [ ] Campo travado para usuários comuns
- [ ] Buscar ao digitar (debounce de 300ms)
- [ ] Mostrar sugestões em dropdown
- [ ] Selecionar usuário ao clicar
- [ ] Limpar seleção (botão X)
- [ ] Fechar sugestões ao clicar fora
- [ ] Enviar `usuario_id` no filtro
- [ ] Coluna "Usuário" na tabela (apenas gestor)
- [ ] Testar com diferentes termos de busca

---

## 📋 RESUMO DAS MUDANÇAS

| Item | Antes | Agora |
|------|-------|-------|
| **Tipo de campo** | Select fixo | Autocomplete dinâmico |
| **Endpoint** | `/api/chamados/usuarios` | `/api/chamados/usuarios?busca={termo}` |
| **Busca** | Não tinha | Busca no nome do usuário |
| **Limite** | Todos | Máximo 20 por busca |
| **UX** | Lista fixa | Digite e aparecem sugestões |

---

**Backend atualizado e pronto! 🎉**
