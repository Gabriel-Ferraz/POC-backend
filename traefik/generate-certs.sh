#!/bin/bash
# Gera certificado auto-assinado para HTTPS local
# Execute uma vez antes de subir os containers: ./traefik/generate-certs.sh

DIR="$(cd "$(dirname "$0")" && pwd)/certs"
mkdir -p "$DIR"

openssl req -x509 -newkey rsa:4096 -sha256 -days 3650 -nodes \
  -keyout "$DIR/key.pem" \
  -out "$DIR/cert.pem" \
  -subj "/CN=localhost" \
  -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"

echo "Certificado gerado em $DIR"
echo "  cert.pem  — certificado público"
echo "  key.pem   — chave privada"
