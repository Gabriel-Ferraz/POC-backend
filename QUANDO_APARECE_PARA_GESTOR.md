# 📋 QUANDO A SOLICITAÇÃO APARECE PARA O GESTOR?

## 🎯 RESPOSTA RÁPIDA

A solicitação **APARECE** para o Gestor do Contrato **APENAS** quando:

✅ **Todos os 5 anexos obrigatórios forem enviados para aprovação**

Status da solicitação muda para: `aguardando_aprovacao_anexos`

---

## 🔄 FLUXO DETALHADO

### 1️⃣ Responsável Técnico cria solicitação
- Status: `pendente`
- 5 anexos criados automaticamente com status: `pendente`
- **❌ NÃO aparece para o Gestor**

### 2️⃣ Responsável faz upload do 1º anexo
- Anexo 1 muda para: `anexo_cadastrado`
- Solicitação continua: `pendente`
- **❌ NÃO aparece para o Gestor**

### 3️⃣ Responsável faz upload do 2º, 3º, 4º anexo
- Anexos mudam para: `anexo_cadastrado`
- Solicitação continua: `pendente`
- **❌ NÃO aparece para o Gestor**

### 4️⃣ Responsável faz upload do 5º anexo (último)
- Anexo 5 muda para: `anexo_cadastrado`
- Solicitação continua: `pendente`
- **❌ AINDA NÃO aparece para o Gestor**

### 5️⃣ Responsável clica em "Enviar Todos para Aprovação"
- Todos os 5 anexos mudam para: `aguardando_aprovacao`
- Solicitação muda para: `aguardando_aprovacao_anexos`
- **✅ AGORA SIM aparece para o Gestor!**

---

## 📡 ENDPOINT DO GESTOR

```
GET /api/gestor/solicitacoes-pendentes
```

**Filtro implementado:**

```php
$solicitacoes = SolicitacaoPagamento::whereIn('status', [
    'aguardando_aprovacao_anexos',
    'anexos_recusados', // Para reenvios
])
```

**Ou seja, o Gestor vê solicitações com status:**

1. `aguardando_aprovacao_anexos` - Primeira vez que todos anexos foram enviados
2. `anexos_recusados` - Quando já recusou algum anexo e está aguardando correção (mas ainda pode visualizar)

---

## 💡 POR QUE FUNCIONA ASSIM?

### Motivos técnicos e de negócio:

1. **Evita trabalho desnecessário do Gestor**
   - Não adianta o Gestor ver solicitação com 2 anexos de 5
   - Só faz sentido aprovar quando TODOS os anexos estiverem enviados

2. **Responsável Técnico tem tempo para organizar**
   - Pode fazer upload aos poucos
   - Só quando tiver certeza que todos estão corretos, envia

3. **Fluxo claro e objetivo**
   - Upload = preparação (privado)
   - Enviar para aprovação = ação formal (público para o Gestor)

---

## 🔁 REENVIO (Após Recusa)

Se o Gestor recusar algum anexo:

1. Status da solicitação muda para: `anexos_recusados`
2. **✅ Continua aparecendo para o Gestor** (para ele acompanhar)
3. Responsável Técnico corrige os anexos recusados
4. Responsável clica em "Enviar Todos para Aprovação" novamente
5. Status volta para: `aguardando_aprovacao_anexos`
6. **✅ Gestor vê novamente na lista de pendentes**

---

## 📊 RESUMO VISUAL

```
┌───────────────────────────────────────────────────────────┐
│ AÇÕES DO RESPONSÁVEL TÉCNICO                              │
└───────────────────────────────────────────────────────────┘
                         │
                         ▼
        ┌────────────────────────────────┐
        │ Cria Solicitação               │
        │ Status: pendente               │
        │ 5 anexos criados               │
        └────────────────┬───────────────┘
                         │
                         ▼
        ┌────────────────────────────────┐
        │ Upload Anexo 1                 │       ❌ Gestor não vê
        │ Anexo 1: anexo_cadastrado      │
        └────────────────┬───────────────┘
                         │
                         ▼
        ┌────────────────────────────────┐
        │ Upload Anexo 2                 │       ❌ Gestor não vê
        │ Anexo 2: anexo_cadastrado      │
        └────────────────┬───────────────┘
                         │
                         ▼
        ┌────────────────────────────────┐
        │ Upload Anexo 3                 │       ❌ Gestor não vê
        │ Anexo 3: anexo_cadastrado      │
        └────────────────┬───────────────┘
                         │
                         ▼
        ┌────────────────────────────────┐
        │ Upload Anexo 4                 │       ❌ Gestor não vê
        │ Anexo 4: anexo_cadastrado      │
        └────────────────┬───────────────┘
                         │
                         ▼
        ┌────────────────────────────────┐
        │ Upload Anexo 5                 │       ❌ Gestor não vê
        │ Anexo 5: anexo_cadastrado      │
        └────────────────┬───────────────┘
                         │
                         ▼
        ┌────────────────────────────────┐
        │ "Enviar Todos para Aprovação"  │
        │ Todos anexos: aguardando_aprv  │
        │ Solicitação: aguard_aprv_anex  │
        └────────────────┬───────────────┘
                         │
                         ▼
        ┌────────────────────────────────┐
        │   ✅ APARECE PARA O GESTOR     │
        │                                │
        │  GET /api/gestor/solicitacoes  │
        │       -pendentes               │
        └────────────────────────────────┘
```

---

## ✅ IMPLEMENTAÇÃO NO CÓDIGO

### Método `enviarTodos` no AnexoController:

```php
public function enviarTodos(Request $request, int $solicitacaoId): JsonResponse
{
    // Verificar se todos os anexos têm arquivo
    $anexosPendentes = $solicitacao->anexos->filter(function ($anexo) {
        return !$anexo->arquivo_path; // Se algum não tiver arquivo, bloqueia
    });

    if ($anexosPendentes->count() > 0) {
        return response()->json([
            'message' => 'Existem anexos pendentes de envio',
            'anexos_pendentes' => [...],
        ], 400);
    }

    // Só chega aqui se TODOS os 5 anexos tiverem arquivo
    $solicitacao->anexos()->update([
        'status' => 'aguardando_aprovacao',
    ]);

    $solicitacao->update([
        'status' => 'aguardando_aprovacao_anexos', // ← AQUI que aparece pro Gestor
    ]);
}
```

### Listagem do Gestor:

```php
public function solicitacoesPendentes(Request $request): JsonResponse
{
    $solicitacoes = SolicitacaoPagamento::whereIn('status', [
        'aguardando_aprovacao_anexos', // ← Primeira vez
        'anexos_recusados',            // ← Reenvios
    ])->get();
}
```

---

## 🎯 CONCLUSÃO

**Momento exato que aparece para o Gestor:**

📅 Quando o Responsável Técnico clica no botão **"Enviar Todos para Aprovação"**

**Pré-requisito:**
- Todos os 5 anexos devem ter arquivo (PDF) enviado

**Status que faz aparecer:**
- `aguardando_aprovacao_anexos` (primeira vez)
- `anexos_recusados` (reenvios após correção)

---

**🚀 Implementado e funcionando!**
