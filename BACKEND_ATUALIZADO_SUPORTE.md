# ✅ BACKEND ATUALIZADO - Sistema de Suporte

## 🎯 MUDANÇAS IMPLEMENTADAS

### 1. Migrations Atualizadas

#### Tabela `chamados`
- ✅ Renomeado `respondido_em` → `data_ultima_resposta`
- ✅ Renomeado `concluido_em` → `data_conclusao`

#### Tabela `mensagens_chamado`
- ✅ Adicionado campo `tipo` ENUM('abertura', 'resposta', 'conclusao')

#### Tabela `anexos_chamado`
- ✅ Renomeado `arquivo` → `caminho`
- ✅ Adicionado `nome_salvo` (nome do arquivo no servidor)
- ✅ Adicionado `tamanho` (bytes)
- ✅ Adicionado `tipo` (mime type)
- ✅ Adicionado `enviado_por_usuario_id` (quem enviou)

---

### 2. Models Atualizados

#### `Chamado.php`
```php
protected $fillable = [
    'usuario_id',
    'modulo',
    'assunto',
    'mensagem',
    'status',
    'data_ultima_resposta',  // ✅ Atualizado
    'data_conclusao',        // ✅ Atualizado
];
```

#### `MensagemChamado.php`
```php
protected $fillable = [
    'chamado_id',
    'usuario_id',
    'tipo',        // ✅ Novo
    'mensagem',
];

// ✅ Novo relacionamento
public function anexos()
{
    return $this->hasMany(AnexoChamado::class, 'mensagem_id');
}
```

#### `AnexoChamado.php`
```php
protected $fillable = [
    'chamado_id',
    'mensagem_id',
    'nome_original',
    'nome_salvo',                   // ✅ Novo
    'caminho',                      // ✅ Renomeado
    'tamanho',                      // ✅ Novo
    'tipo',                         // ✅ Novo
    'enviado_por_usuario_id',       // ✅ Novo
];

// ✅ Novo relacionamento
public function enviadoPor()
{
    return $this->belongsTo(User::class, 'enviado_por_usuario_id');
}
```

---

### 3. Controller Atualizado

#### `ChamadoController.php`

##### `store()` - Criar Chamado
- ✅ Cria mensagem de abertura na timeline
- ✅ Salva anexos com metadados completos (tamanho, tipo, nome_salvo)
- ✅ Armazena em `storage/app/private/chamados/{id}/`
- ✅ Retorna `anexos_count`

##### `index()` - Listar Chamados
- ✅ Retorna `data_ultima_resposta` (antes era `data_resposta`)
- ✅ Retorna `total_mensagens` (conta mensagens da timeline)
- ✅ Retorna `total_anexos`

##### `show()` - Ver Detalhes
- ✅ Retorna timeline completa a partir das mensagens
- ✅ Cada mensagem tem seus anexos
- ✅ Anexos com tamanho formatado e URL de download

##### `responder()` - Responder Chamado
- ✅ Cria mensagem do tipo 'resposta' na timeline
- ✅ Atualiza `data_ultima_resposta`
- ✅ Salva anexos com metadados completos
- ✅ Retorna dados da mensagem criada

##### `concluir()` - Concluir Chamado
- ✅ Atualiza `data_conclusao` (antes era `concluido_em`)

##### `downloadAnexo()` - Download de Anexo ✨ NOVO
- ✅ Valida permissões (gestor ou dono do chamado)
- ✅ Faz download do arquivo do storage privado

##### `listarUsuarios()` - Autocomplete de Usuários
- ✅ Busca case-insensitive com ILIKE
- ✅ Limite de 20 resultados
- ✅ Retorna `usuario_atual` para não-gestores
- ✅ Retorna array `usuarios` para gestores

---

### 4. Rotas Atualizadas

```php
Route::prefix('chamados')->group(function () {
    Route::get('/', [ChamadoController::class, 'index']);
    Route::get('/usuarios', [ChamadoController::class, 'listarUsuarios']);
    Route::post('/', [ChamadoController::class, 'store']);
    Route::get('/{id}', [ChamadoController::class, 'show']);
    Route::post('/{id}/responder', [ChamadoController::class, 'responder']);
    Route::post('/{id}/anexos', [ChamadoController::class, 'anexar']);
    Route::post('/{id}/concluir', [ChamadoController::class, 'concluir']);
    Route::get('/anexos/{id}/download', [ChamadoController::class, 'downloadAnexo']); // ✅ NOVO
});
```

---

## 📡 ENDPOINTS ATUALIZADOS

### POST `/api/chamados` - Criar Chamado

#### Body (FormData)
```
modulo: string
assunto: string
mensagem: string
anexos[]: File[] (opcional, max 10MB cada, formatos: pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif)
```

#### Response (201)
```json
{
  "message": "Chamado criado com sucesso",
  "chamado": {
    "id": 5,
    "protocolo": "#5",
    "modulo": "Portal do Fornecedor",
    "assunto": "Erro ao acessar sistema",
    "usuario": "João Silva (Responsável Técnico)",
    "status": "aberto",
    "data_cadastro": "10/05/2026 14:30",
    "data_abertura": "10/05/2026",
    "anexos_count": 2
  }
}
```

---

### GET `/api/chamados` - Listar Chamados

#### Query Parameters
- `protocolo`: String
- `modulo`: String
- `assunto`: String
- `status`: String (múltiplos: "aberto,em_atendimento")
- `usuario_id`: Integer (apenas gestores)
- `data_cadastro_inicio`: Date (Y-m-d)
- `data_cadastro_fim`: Date (Y-m-d)
- `data_resposta_inicio`: Date (Y-m-d)
- `data_resposta_fim`: Date (Y-m-d)

#### Response (200)
```json
{
  "chamados": [
    {
      "id": 5,
      "protocolo": "#5",
      "modulo": "Portal do Fornecedor",
      "assunto": "Erro ao acessar sistema",
      "usuario": "João Silva (Responsável Técnico)",
      "status": "aberto",
      "data_abertura": "10/05/2026",
      "data_cadastro": "10/05/2026 14:30",
      "data_ultima_resposta": null,
      "total_mensagens": 1,
      "total_anexos": 2
    }
  ]
}
```

---

### GET `/api/chamados/usuarios?busca={termo}` - Autocomplete

#### Query Parameters
- `busca`: String (opcional, busca case-insensitive)

#### Response para Gestor
```json
{
  "usuarios": [
    {
      "id": 1,
      "name": "João Silva",
      "perfil": "responsavel_tecnico",
      "perfil_label": "Responsável Técnico"
    }
  ]
}
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

---

### GET `/api/chamados/{id}` - Ver Detalhes

#### Response (200)
```json
{
  "chamado": {
    "id": 5,
    "protocolo": "#5",
    "modulo": "Portal do Fornecedor",
    "assunto": "Erro ao acessar sistema",
    "usuario": "João Silva",
    "status": "aberto",
    "data_abertura": "10/05/2026",
    "data_cadastro": "10/05/2026 14:30",
    "data_ultima_resposta": null,
    "data_conclusao": null,
    "mensagem_inicial": "Descrição do problema..."
  },
  "timeline": [
    {
      "id": 1,
      "tipo": "abertura",
      "usuario": "João Silva",
      "mensagem": "Descrição do problema...",
      "data": "10/05/2026 14:30",
      "anexos": [
        {
          "id": 1,
          "nome": "screenshot.png",
          "tamanho": "245.5 KB",
          "url": "/api/chamados/anexos/1/download"
        }
      ]
    },
    {
      "id": 2,
      "tipo": "resposta",
      "usuario": "Ana Paula",
      "mensagem": "Estamos analisando...",
      "data": "10/05/2026 15:45",
      "anexos": []
    }
  ]
}
```

---

### POST `/api/chamados/{id}/responder` - Responder

#### Body (FormData)
```
mensagem: string
anexos[]: File[] (opcional)
```

#### Response (200)
```json
{
  "message": "Resposta adicionada com sucesso",
  "mensagem": {
    "id": 2,
    "tipo": "resposta",
    "usuario": "Ana Paula",
    "mensagem": "Estamos analisando o problema...",
    "data": "10/05/2026 15:45",
    "anexos_count": 0
  }
}
```

---

### GET `/api/chamados/anexos/{id}/download` - Download Anexo ✨ NOVO

#### Response
Faz download do arquivo com `Content-Disposition: attachment`

---

## 🔧 COMO RODAR AS MIGRAÇÕES

```bash
# Rodar migrations
docker compose exec app php artisan migrate

# Se precisar resetar e recriar tudo
docker compose exec app php artisan migrate:fresh --seed
```

---

## 🧪 TESTES RECOMENDADOS

### 1. Criar Chamado com Anexos
```bash
curl -X POST http://localhost:3333/api/chamados \
  -H "Authorization: Bearer {token}" \
  -F "modulo=Portal do Fornecedor" \
  -F "assunto=Erro ao acessar sistema" \
  -F "mensagem=Descrição do problema" \
  -F "anexos[]=@screenshot.png"
```

### 2. Listar Chamados
```bash
curl -X GET "http://localhost:3333/api/chamados?status=aberto,em_atendimento" \
  -H "Authorization: Bearer {token}"
```

### 3. Buscar Usuários (Autocomplete)
```bash
curl -X GET "http://localhost:3333/api/chamados/usuarios?busca=silva" \
  -H "Authorization: Bearer {token_gestor}"
```

### 4. Ver Detalhes do Chamado
```bash
curl -X GET http://localhost:3333/api/chamados/5 \
  -H "Authorization: Bearer {token}"
```

### 5. Responder Chamado
```bash
curl -X POST http://localhost:3333/api/chamados/5/responder \
  -H "Authorization: Bearer {token_gestor}" \
  -F "mensagem=Estamos analisando o problema"
```

### 6. Download de Anexo
```bash
curl -X GET http://localhost:3333/api/chamados/anexos/1/download \
  -H "Authorization: Bearer {token}" \
  --output arquivo.pdf
```

---

## ✅ CHECKLIST

- [x] Migrations atualizadas
- [x] Models atualizados com fillable corretos
- [x] Controller com métodos atualizados
- [x] Rota de download de anexo
- [x] Timeline com mensagens
- [x] Anexos vinculados às mensagens
- [x] Autocomplete case-insensitive
- [x] Validação de permissões
- [x] Documentação completa

---

**🎉 Backend completamente atualizado conforme especificação!**
