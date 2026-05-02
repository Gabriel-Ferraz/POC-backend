# ✅ IMPLEMENTADO: Responder Chamados com Anexos

## 🎯 FUNCIONALIDADE

Sistema completo para responder chamados com:
- Validação de permissões
- Histórico de mensagens (timeline)
- Upload de múltiplos anexos
- Atualização automática de status

---

## 📡 ENDPOINT IMPLEMENTADO

### POST `/api/chamados/{id}/responder`

#### Headers
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

#### Body (FormData)
```
mensagem: string (obrigatório)
anexos[]: File[] (opcional, múltiplos)
```

#### Validações
- `mensagem`: obrigatório, string
- `anexos`: opcional, array
  - Máximo 10MB por arquivo
  - Formatos: pdf, doc, docx, xls, xlsx, png, jpg, jpeg, gif

---

## 🔐 PERMISSÕES IMPLEMENTADAS

### Quem pode responder:
✅ Criador do chamado (usuário que abriu)
✅ Gestor de Suporte (`gestor_suporte`)
✅ Gestor de Contrato (`gestor_contrato`)

### Regras:
- Verifica se o usuário é o criador OU se é gestor
- Retorna 403 se não tiver permissão
- Retorna 400 se chamado já estiver concluído

---

## 🔄 COMPORTAMENTO DO SISTEMA

### Ao Enviar Resposta:

1. ✅ Cria nova mensagem com `tipo = 'resposta'`
2. ✅ Salva anexos vinculados à mensagem
3. ✅ Atualiza `data_ultima_resposta` do chamado
4. ✅ Muda status para `em_atendimento` (apenas se estiver `aberto`)

---

## 📤 RESPONSES

### Sucesso (200 OK)
```json
{
  "message": "Resposta adicionada com sucesso",
  "mensagem": {
    "id": 5,
    "tipo": "resposta",
    "usuario": "Ana Paula",
    "mensagem": "Estamos analisando o problema...",
    "data": "02/05/2026 15:45",
    "anexos_count": 2
  }
}
```

### Sem Permissão (403)
```json
{
  "message": "Você não tem permissão para responder este chamado."
}
```

### Chamado Concluído (400)
```json
{
  "message": "Chamado já foi concluído"
}
```

### Validação (422)
```json
{
  "message": "Dados inválidos",
  "errors": {
    "mensagem": ["The mensagem field is required."]
  }
}
```

---

## 📊 ESTRUTURA DO ENDPOINT `show()`

### GET `/api/chamados/{id}`

#### Response Atualizado
```json
{
  "chamado": {
    "id": 4,
    "protocolo": "#4",
    "modulo": "Portal do Fornecedor",
    "assunto": "Erro ao acessar sistema",
    "usuario": "João Silva (Responsável Técnico)",
    "status": "em_atendimento",
    "data_abertura": "02/05/2026",
    "data_cadastro": "02/05/2026 13:38",
    "data_ultima_resposta": "02/05/2026 15:45",
    "data_conclusao": null,
    "mensagem_inicial": "Erro ao acessar sistema"
  },
  "timeline": [
    {
      "id": 4,
      "tipo": "abertura",
      "usuario": "João Silva (Responsável Técnico)",
      "mensagem": "Erro ao acessar sistema",
      "data": "02/05/2026 13:38",
      "anexos": [
        {
          "id": 1,
          "nome": "screenshot.png",
          "tamanho": "33.6 KB",
          "url": "http://localhost:3333/api/chamados/anexos/1/download"
        }
      ]
    },
    {
      "id": 5,
      "tipo": "resposta",
      "usuario": "Ana Paula",
      "mensagem": "Estamos analisando o problema...",
      "data": "02/05/2026 15:45",
      "anexos": [
        {
          "id": 2,
          "nome": "solucao.pdf",
          "tamanho": "245.2 KB",
          "url": "http://localhost:3333/api/chamados/anexos/2/download"
        }
      ]
    }
  ]
}
```

---

## 🧪 TESTES

### 1. Responder sem anexos
```bash
curl -X POST http://localhost:3333/api/chamados/4/responder \
  -H "Authorization: Bearer {token}" \
  -F "mensagem=Estamos analisando o problema"
```

### 2. Responder com anexos
```bash
curl -X POST http://localhost:3333/api/chamados/4/responder \
  -H "Authorization: Bearer {token}" \
  -F "mensagem=Segue solução em anexo" \
  -F "anexos[]=@solucao.pdf" \
  -F "anexos[]=@print.png"
```

### 3. Tentar responder sem permissão
```bash
# Usuário que não é criador nem gestor
curl -X POST http://localhost:3333/api/chamados/4/responder \
  -H "Authorization: Bearer {token_outro_usuario}" \
  -F "mensagem=Teste"

# Esperado: 403 Forbidden
```

### 4. Ver timeline atualizada
```bash
curl -X GET http://localhost:3333/api/chamados/4 \
  -H "Authorization: Bearer {token}"
```

---

## 📋 CHECKLIST

### Backend
- [x] Validação de permissões (criador OU gestor)
- [x] Criar mensagem com tipo 'resposta'
- [x] Salvar anexos vinculados à mensagem
- [x] Atualizar `data_ultima_resposta`
- [x] Mudar status para `em_atendimento` (se aberto)
- [x] Response com dados da mensagem criada
- [x] Endpoint `show()` retorna `mensagem_inicial`
- [x] Timeline ordenada por data (ASC)
- [x] Relacionamentos corretos nos models
- [x] Rota registrada

### Arquivos Modificados
- ✅ `app/Http/Controllers/ChamadoController.php`
  - Método `responder()`: adicionada validação de permissão
  - Método `show()`: adicionado campo `mensagem_inicial`

### Próximos Passos (Frontend)
- [ ] Formulário para enviar resposta
- [ ] Upload múltiplo de arquivos
- [ ] Exibir timeline com todas as mensagens
- [ ] Botão de download nos anexos
- [ ] Recarregar timeline após enviar resposta
- [ ] Mostrar indicador de quem respondeu
- [ ] Desabilitar resposta se chamado concluído

---

## 🎯 FLUXO COMPLETO

```
1. Usuário acessa detalhes do chamado
   GET /api/chamados/4

2. Sistema retorna chamado + timeline

3. Usuário digita resposta e anexa arquivos

4. Frontend envia:
   POST /api/chamados/4/responder
   Body: { mensagem, anexos[] }

5. Backend valida permissões

6. Backend cria mensagem tipo 'resposta'

7. Backend salva anexos

8. Backend atualiza data_ultima_resposta e status

9. Backend retorna mensagem criada

10. Frontend recarrega timeline
    GET /api/chamados/4

11. Nova resposta aparece na timeline
```

---

## 🔗 RELACIONAMENTOS

```
Chamado (1) → (N) Mensagens
  ├─ tipo: 'abertura' | 'resposta'
  └─ (N) Anexos

Mensagem (1) → (N) Anexos
  └─ vinculados à mensagem específica

Anexo (N) → (1) Mensagem
  └─ mensagem_id (pode ser null para abertura)
```

---

**🎉 Sistema de respostas implementado e pronto para uso!**

## 📝 OBSERVAÇÕES IMPORTANTES

1. **Status do Chamado:**
   - Quando está `aberto` e recebe resposta → muda para `em_atendimento`
   - Quando já está `em_atendimento` e recebe resposta → mantém `em_atendimento`
   - Quando está `concluido` → não aceita mais respostas (retorna erro 400)

2. **Anexos:**
   - São salvos em `storage/app/public/chamados/{chamado_id}/`
   - Nome é sanitizado (timestamp + slug)
   - Vinculados à mensagem específica via `mensagem_id`

3. **Timeline:**
   - Ordenada por `created_at` ASC (mais antiga primeiro)
   - Primeira mensagem é sempre tipo 'abertura'
   - Demais são tipo 'resposta'

4. **Permissões:**
   - Criador pode sempre responder seus próprios chamados
   - Gestores podem responder qualquer chamado
   - Outros usuários não podem responder
