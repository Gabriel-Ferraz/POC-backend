#!/bin/bash

echo "======================================"
echo "TESTE: Endpoint /api/chamados/usuarios"
echo "======================================"
echo ""

# Cores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# URL base
BASE_URL="http://localhost:3333"

echo "1️⃣  Login como USUÁRIO COMUM (responsavel_tecnico)"
echo "--------------------------------------"

# Login como usuário comum
RESPONSE_COMUM=$(curl -s -X POST "$BASE_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"cpf": "12345678900", "password": "senha123"}')

TOKEN_COMUM=$(echo $RESPONSE_COMUM | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -n "$TOKEN_COMUM" ]; then
    echo -e "${GREEN}✅ Login bem-sucedido${NC}"
    echo "Token: ${TOKEN_COMUM:0:20}..."

    echo ""
    echo "Chamando GET /api/chamados/usuarios"
    RESPONSE=$(curl -s -X GET "$BASE_URL/api/chamados/usuarios" \
      -H "Authorization: Bearer $TOKEN_COMUM")

    echo "Response:"
    echo $RESPONSE | jq '.' 2>/dev/null || echo $RESPONSE
else
    echo -e "${RED}❌ Erro no login${NC}"
    echo $RESPONSE_COMUM
fi

echo ""
echo "======================================"
echo ""

echo "2️⃣  Login como GESTOR DE SUPORTE"
echo "--------------------------------------"

# Login como gestor de suporte
RESPONSE_GESTOR=$(curl -s -X POST "$BASE_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"cpf": "55566677788", "password": "senha123"}')

TOKEN_GESTOR=$(echo $RESPONSE_GESTOR | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -n "$TOKEN_GESTOR" ]; then
    echo -e "${GREEN}✅ Login bem-sucedido${NC}"
    echo "Token: ${TOKEN_GESTOR:0:20}..."

    echo ""
    echo "Chamando GET /api/chamados/usuarios"
    RESPONSE=$(curl -s -X GET "$BASE_URL/api/chamados/usuarios" \
      -H "Authorization: Bearer $TOKEN_GESTOR")

    echo "Response:"
    echo $RESPONSE | jq '.' 2>/dev/null || echo $RESPONSE

    echo ""
    echo "Chamando GET /api/chamados/usuarios?busca=silva"
    RESPONSE_BUSCA=$(curl -s -X GET "$BASE_URL/api/chamados/usuarios?busca=silva" \
      -H "Authorization: Bearer $TOKEN_GESTOR")

    echo "Response com busca:"
    echo $RESPONSE_BUSCA | jq '.' 2>/dev/null || echo $RESPONSE_BUSCA
else
    echo -e "${RED}❌ Erro no login${NC}"
    echo $RESPONSE_GESTOR
fi

echo ""
echo "======================================"
echo ""

echo "3️⃣  Login como GESTOR DE CONTRATO"
echo "--------------------------------------"

# Login como gestor de contrato
RESPONSE_GESTOR_CONTRATO=$(curl -s -X POST "$BASE_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"cpf": "98765432100", "password": "senha123"}')

TOKEN_GESTOR_CONTRATO=$(echo $RESPONSE_GESTOR_CONTRATO | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -n "$TOKEN_GESTOR_CONTRATO" ]; then
    echo -e "${GREEN}✅ Login bem-sucedido${NC}"
    echo "Token: ${TOKEN_GESTOR_CONTRATO:0:20}..."

    echo ""
    echo "Chamando GET /api/chamados/usuarios"
    RESPONSE=$(curl -s -X GET "$BASE_URL/api/chamados/usuarios" \
      -H "Authorization: Bearer $TOKEN_GESTOR_CONTRATO")

    echo "Response:"
    echo $RESPONSE | jq '.' 2>/dev/null || echo $RESPONSE
else
    echo -e "${RED}❌ Erro no login${NC}"
    echo $RESPONSE_GESTOR_CONTRATO
fi

echo ""
echo "======================================"
echo "TESTES CONCLUÍDOS"
echo "======================================"
