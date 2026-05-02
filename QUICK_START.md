# ⚡ QUICK START - POC SÃO JOSÉ DOS PINHAIS

## 🚀 Setup em 5 passos

### 1️⃣ Instalar dependências
```bash
composer install
```

### 2️⃣ Configurar ambiente
```bash
cp .env.example .env
php artisan key:generate
```

Edite o `.env` e configure o banco:
```env
DB_DATABASE=poc_sjp
DB_USERNAME=root
DB_PASSWORD=
```

### 3️⃣ Criar banco e rodar migrations
```bash
# Criar banco (MySQL)
mysql -u root -p -e "CREATE DATABASE poc_sjp"

# Rodar migrations e seeders
php artisan migrate:fresh --seed
```

### 4️⃣ Criar link do storage
```bash
php artisan storage:link
```

### 5️⃣ Iniciar servidor
```bash
php artisan serve
```

✅ **API rodando em:** `http://localhost:8000`

---

## 🧪 Teste rápido

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"cpf":"12345678900","password":"senha123"}'
```

Copie o `token` retornado.

### Listar empenhos
```bash
curl -X GET http://localhost:8000/api/fornecedor/empenhos \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

---

## 👥 Usuários (senha: senha123)

| CPF | Perfil |
|-----|--------|
| 12345678900 | Responsável Técnico |
| 98765432100 | Gestor Contrato |
| 11122233344 | Operador PMSJP |
| 55566677788 | Gestor Suporte |
| 99988877766 | Operador Orçamentário |

---

## 📡 Principais Endpoints

### Autenticação
- `POST /api/auth/login` - Login
- `GET /api/auth/me` - Dados do usuário

### Portal do Fornecedor
- `GET /api/fornecedor/empenhos` - Empenhos

### Solicitações
- `GET /api/empenhos/{id}/solicitacoes` - Listar
- `POST /api/empenhos/{id}/solicitacoes` - Criar
- `GET /api/solicitacoes/{id}` - Detalhe
- `POST /api/solicitacoes/{id}/cancelar` - Cancelar

### Anexos
- `GET /api/solicitacoes/{id}/anexos` - Listar
- `POST /api/solicitacoes/{id}/anexos` - Upload
- `POST /api/anexos/{id}/aprovar` - Aprovar
- `POST /api/anexos/{id}/recusar` - Recusar

### Suporte
- `GET /api/chamados` - Listar
- `POST /api/chamados` - Criar
- `GET /api/chamados/{id}` - Detalhe
- `POST /api/chamados/{id}/responder` - Responder

### Prestação de Contas
- `POST /api/prestacao-contas/exportar` - Exportar
- `GET /api/prestacao-contas/exportacoes` - Listar

### Orçamentário
- `GET /api/orcamentario/leis-atos` - Listar leis
- `POST /api/orcamentario/leis-atos` - Criar lei
- `GET /api/orcamentario/alteracoes` - Listar alterações
- `POST /api/orcamentario/alteracoes` - Criar alteração

---

## 🐛 Troubleshooting

### Erro de conexão MySQL
```bash
# Verifique se o MySQL está rodando
sudo service mysql status

# Inicie se necessário
sudo service mysql start
```

### Erro de permissão storage
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Limpar cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Recriar banco do zero
```bash
php artisan migrate:fresh --seed --force
```

---

## 📚 Documentação completa

- **Implementação:** `IMPLEMENTACAO_POC.md`
- **Endpoints:** `API_ENDPOINTS.md`
- **Requisitos:** `guia_poc_sao_jose_pinhais.md`

---

## ✅ Checklist pré-apresentação

- [ ] Backend rodando sem erros
- [ ] Login funcionando
- [ ] Seeders popularam dados demo
- [ ] Upload de anexos testado
- [ ] CORS configurado para frontend
- [ ] Testar em Chrome e Firefox

---

**🎯 Pronto para integrar com o frontend!**
