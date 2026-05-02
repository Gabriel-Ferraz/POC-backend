# Guia de Construção da POC — Prefeitura Municipal de São José dos Pinhais

**Data da POC:** Segunda-feira, 04/05  
**Frontend:** Next.js + Tailwind CSS + shadcn/ui  
**Divisão:** Você no frontend, seu amigo no backend  
**Objetivo:** Construir uma demonstração funcional, objetiva e alinhada aos requisitos eliminatórios do anexo.

---

## 1. Objetivo da POC

A POC não precisa ser um sistema completo de produção, mas precisa demonstrar com segurança que a equipe domina:

- Arquitetura MVC / separação de responsabilidades;
- Fluxos de negócio da Prefeitura;
- Telas funcionais com dados navegáveis;
- Upload e consulta de anexos;
- Controle de status e histórico;
- Compatibilidade com navegadores;
- Perfil de acesso por tipo de usuário;
- Geração/consulta de documentos e relatórios;
- Continuidade do ambiente tecnológico exigido no edital.

A avaliação é objetiva: cada item será marcado como **conforme** ou **não conforme**. Um item não conforme pode eliminar a licitante. Por isso, a prioridade é entregar o fluxo exigido funcionando de ponta a ponta.

---

## 2. Estratégia Geral de Construção

Como o prazo é curto, a POC deve ser construída como uma aplicação demonstrável, com foco em fluxos críticos e dados simulados/controlados.

### Estratégia recomendada

- Criar um painel web em Next.js.
- Usar componentes shadcn/ui para acelerar telas.
- Usar layout administrativo consistente.
- Criar dados mockados inicialmente no frontend.
- Integrar com backend conforme as rotas forem ficando prontas.
- Garantir que todos os botões exigidos abram uma tela, modal ou executem uma ação simulada.
- Registrar histórico visual de tudo: criação, tramitação, recusa, aprovação e cancelamento.

---

## 3. Módulos que precisam existir na demonstração

A POC deve conter, no mínimo, os seguintes módulos:

1. Ambiente / Arquitetura / Compatibilidade;
2. Portal do Fornecedor;
3. Solicitação de Pagamento;
4. Gestão de Anexos;
5. Consulta de Andamento da Solicitação;
6. Suporte ao Usuário;
7. Exportador de Prestação de Contas;
8. Orçamentário — Alteração Orçamentária.

---

## 4. Estrutura sugerida de rotas no Next.js

```txt
src/
├── app/
│   ├── login/
│   │   └── page.tsx
│   ├── portal-fornecedor/
│   │   ├── page.tsx
│   │   ├── empenhos/
│   │   │   └── page.tsx
│   │   ├── empenhos/[id]/solicitacoes/
│   │   │   └── page.tsx
│   │   ├── solicitacoes/[id]/anexos/
│   │   │   └── page.tsx
│   │   ├── solicitacoes/[id]/cancelar/
│   │   │   └── page.tsx
│   │   └── solicitacoes/[id]/informacoes/
│   │       └── page.tsx
│   ├── suporte/
│   │   ├── page.tsx
│   │   ├── novo/
│   │   │   └── page.tsx
│   │   └── chamados/[id]/
│   │       └── page.tsx
│   ├── prestacao-contas/
│   │   └── page.tsx
│   ├── orcamentario/
│   │   ├── leis-atos/
│   │   │   └── page.tsx
│   │   └── alteracoes/
│   │       ├── page.tsx
│   │       └── [id]/page.tsx
│   └── demonstracao-tecnica/
│       └── page.tsx
├── components/
│   ├── layout/
│   ├── portal-fornecedor/
│   ├── solicitacoes/
│   ├── suporte/
│   ├── prestacao-contas/
│   └── orcamentario/
├── lib/
│   ├── api.ts
│   ├── mocks.ts
│   └── formatters.ts
└── types/
```

---

## 5. Layout base obrigatório

Todas as telas devem ter uma aparência institucional e administrativa.

### Componentes principais

- Sidebar com módulos;
- Header superior;
- Breadcrumb;
- Botão global de suporte no canto superior direito;
- Área principal com cards e tabelas;
- Feedback visual com badges de status;
- Modal de confirmação para ações críticas;
- Toast para sucesso/erro;
- Tabelas com ações por linha.

### Sidebar sugerida

```txt
Portal do Fornecedor
├── Empenhos
├── Solicitações de Pagamento
├── Andamento

Suporte ao Usuário
├── Meus Chamados
├── Novo Chamado

Prestação de Contas
├── Exportador SIM-AM

Orçamentário
├── Leis e Atos
├── Alterações Orçamentárias

Demonstração Técnica
├── Arquitetura MVC
├── Compatibilidade Browser
```

---

## 6. Perfis de usuário para demonstração

Criar seleção rápida de perfil no topo ou login fake controlado.

### Perfis mínimos

| Perfil | Função |
|---|---|
| Responsável Técnico | Acessa Portal do Fornecedor, cria solicitação, envia anexos, consulta andamento e cancela solicitação |
| Gestor do Contrato | Analisa anexos, aprova ou recusa documentos |
| Operador PMSJP | Tramita fases internas da solicitação |
| Gestor de Suporte | Consulta todos os chamados |
| Usuário comum | Abre e consulta apenas seus próprios chamados |
| Operador Orçamentário | Cadastra leis, atos e alterações orçamentárias |

---

## 7. Tela 1 — Login / Acesso ao Portal do Fornecedor

### Objetivo

Demonstrar acesso seguro do Responsável Técnico ao Portal do Fornecedor.

### Campos

- CPF;
- Senha ou botão “Entrar”;
- Mensagem de usuário inexistente;
- Validação de CPF vinculado ao fornecedor/contrato.

### Comportamentos obrigatórios

- Se CPF válido: entrar no portal;
- Se CPF inválido: exibir mensagem de erro;
- Após login: mostrar o fornecedor vinculado.

### Visual recomendado

- Card centralizado;
- Brasão/nome da Prefeitura no topo;
- Texto: “Portal do Fornecedor”.

---

## 8. Tela 2 — Portal do Fornecedor / Listagem de Empenhos

### Objetivo

Exibir os empenhos relacionados ao fornecedor do Responsável Técnico.

### Campos da tabela

- Número do empenho;
- Data de emissão;
- Valor empenhado;
- Saldo disponível;
- Contrato;
- Status;
- Ações.

### Ações

- Solicitação de Pagamento;
- Informações do Empenho.

### Regras importantes

- Se houver solicitação aberta, o saldo deve aparecer como bloqueado;
- Se não houver saldo, desabilitar ação de solicitar pagamento;
- Mostrar badge: “Disponível”, “Bloqueado”, “Sem saldo”.

---

## 9. Tela 3 — Listagem de Solicitações de Pagamento do Empenho

### Objetivo

Permitir consultar solicitações existentes e iniciar uma nova solicitação.

### Campos da tabela

- Número da solicitação;
- Data;
- Valor;
- Solicitante;
- Situação;
- Documento fiscal;
- Ações.

### Ações por linha

- Anexos;
- Cancelar;
- Informações.

### Botão principal

- Nova Solicitação de Pagamento.

### Regras importantes

- “Anexos” e “Cancelar” só devem ficar disponíveis quando a solicitação estiver pendente ou recusada;
- “Informações” sempre disponível;
- Status visual por badge.

---

## 10. Tela 4 — Nova Solicitação de Pagamento

### Objetivo

Criar uma solicitação de pagamento vinculada a um empenho.

### Campos obrigatórios

#### Dados da solicitação

- Valor da solicitação;
- Observação opcional.

#### Documento fiscal / recibo

- Tipo de documento;
- Número do documento;
- Série;
- Data de emissão;
- Observação opcional.

#### Forma de pagamento

- Conta bancária ou documento/fatura.

Se conta bancária:

- Agência;
- Dígito da agência;
- Conta;
- Dígito da conta;
- Banco;
- Operação;
- Cidade do banco.

### Após salvar

- Criar solicitação com status “Pendente”;
- Redirecionar para a tela de anexos;
- Listar anexos exigidos pelo contrato.

---

## 11. Tela 5 — Gestão de Anexos da Solicitação

### Objetivo

Permitir envio, substituição, visualização e controle dos anexos obrigatórios.

### Lista de anexos mínimos

- Documento Fiscal / Recibo;
- Certidão Negativa de Débitos;
- Certidão Tributária;
- Guia de Previdência Social;
- FGTS.

### Campos da tabela

- Nome do anexo;
- Arquivo enviado;
- Data de envio;
- Situação;
- Motivo da recusa;
- Ações.

### Situações

- Pendente;
- Anexo Cadastrado;
- Aguardando Aprovação;
- Aprovado;
- Recusado.

### Ações

- Procurar arquivo;
- Visualizar;
- Excluir/substituir;
- Enviar todos para aprovação.

### Regras importantes

- Enquanto estiver pendente ou recusado, permitir manipular anexos;
- Após aprovação, bloquear edição;
- Se todos forem enviados, alterar para “Aguardando Aprovação”;
- Gestor deve conseguir aprovar ou recusar;
- Recusa exige motivo obrigatório.

---

## 12. Tela 6 — Aprovação de Anexos pelo Gestor

### Objetivo

Demonstrar que o Gestor do Contrato consegue analisar anexos enviados.

### Campos

- Solicitação;
- Fornecedor;
- Empenho;
- Lista de anexos;
- Visualização do arquivo;
- Campo de motivo para recusa.

### Ações

- Aprovar anexo;
- Recusar anexo;
- Aprovar todos;
- Devolver para correção.

### Regra crítica

Se o documento fiscal/recibo for recusado, a solicitação deve permitir cancelamento e criação de uma nova solicitação.

---

## 13. Tela 7 — Cancelamento de Solicitação de Pagamento

### Objetivo

Permitir cancelamento com registro de motivo.

### Campos

- Data do cancelamento;
- Motivo;
- Botão salvar;
- Botão cancelar.

### Regras importantes

- Motivo obrigatório;
- Registrar no histórico;
- Liberar saldo do empenho;
- Alterar status para “Cancelada”.

---

## 14. Tela 8 — Informações da Solicitação de Pagamento

### Objetivo

Exibir acompanhamento completo e em tempo real da solicitação.

### Componentes obrigatórios

#### 1. Quadro “Andamento”

Stepper horizontal com status:

1. Solicitação de Pagamento;
2. Anexos;
3. Fiscal;
4. Gestor;
5. Liquidação;
6. Secretaria;
7. ISS;
8. Ordem de Pagamento;
9. Autorização;
10. Borderô;
11. Remessa;
12. Pagamento;
13. Pagamento Realizado.

### Cores

- Verde: concluído;
- Amarelo: em andamento;
- Cinza: pendente.

#### 2. Abas

- Geral;
- Trâmites da Solicitação;
- Anexos Pagamento;
- Gestor;
- Comissão de Liquidação;
- Processo;
- Ordem de Pagamento;
- Forma de Pagamento;
- Pagamento Realizado.

### Aba Geral

- Número da solicitação;
- Data;
- Valor;
- Solicitante;
- Fornecedor;
- Empenho;
- Documento fiscal.

### Aba Trâmites

- Data;
- Trâmite;
- Motivo;
- Usuário responsável;
- Observação.

### Aba Anexos

- Nome do anexo;
- Arquivo;
- Situação;
- Usuário;
- Data.

### Aba Forma de Pagamento

- Tipo de pagamento;
- Banco;
- Agência;
- Conta;
- Cidade.

### Aba Pagamento Realizado

- Data/hora do pagamento;
- Comprovante;
- Histórico completo.

---

## 15. Tela 9 — Suporte ao Usuário / Novo Chamado

### Objetivo

Permitir abertura de atendimento ao usuário a partir de qualquer tela.

### Atalho obrigatório

- Ícone de carta ou suporte no header de cada tela.

### Campos

- Módulo do sistema;
- Assunto;
- Mensagem;
- Anexos;
- Botões: Enviar e Limpar.

### Ações de anexo

- Procurar arquivo;
- Visualizar;
- Cancelar envio;
- Adicionar outro anexo.

### Após salvar

- Criar chamado com status “Aberto”;
- Exibir na listagem de chamados;
- Registrar log inicial.

---

## 16. Tela 10 — Consulta de Chamados

### Objetivo

Permitir que o usuário consulte seus chamados e o gestor consulte todos.

### Filtros

- ID do chamado;
- Data de cadastro;
- Data de resposta;
- Módulo;
- Usuário origem;
- Assunto;
- Situação.

### Situações

- Aberto;
- Em Atendimento;
- Concluído.

### Regras por perfil

#### Usuário comum

- Só enxerga os próprios chamados;
- Filtro “Usuário origem” desabilitado.

#### Gestor

- Enxerga todos;
- Filtro “Usuário origem” habilitado.

### Ações

- Log;
- Informações;
- Responder;
- Inserir anexo em chamado em andamento.

### Cores do ícone de log

- Azul: aberto, última mensagem do usuário;
- Laranja: respondido, aguardando usuário verificar;
- Cinza: concluído.

---

## 17. Tela 11 — Detalhe do Chamado / Log

### Objetivo

Exibir mensagens, anexos e histórico do chamado.

### Componentes

- Timeline de mensagens;
- Usuário responsável;
- Data/hora;
- Anexos por mensagem;
- Campo para nova resposta;
- Upload de novo anexo;
- Botão concluir chamado.

---

## 18. Tela 12 — Prestação de Contas / Exportador SIM-AM

### Objetivo

Gerar arquivos para prestação de contas junto ao TCE/SIM-AM.

### Campos

- Ano;
- Módulo;
- Tipo de geração;
- Mês;
- Lista de arquivos disponíveis.

### Lista de arquivos

- PlanoContabil;
- MovimentoMensal;
- Balancete;
- Receita;
- Despesa.

### Ações

- Selecionar arquivo;
- Reordenar lista;
- Exportar;
- Limpar;
- Baixar arquivo gerado.

### Resultado esperado

Após exportar, exibir card verde de sucesso:

- Nome do arquivo;
- Data de exportação;
- Quantidade de registros;
- Botão baixar.

---

## 19. Tela 13 — Orçamentário / Cadastro de Leis e Atos

### Objetivo

Permitir cadastro de leis e atos para uso na alteração orçamentária.

### Campos

- Número da lei/ato;
- Tipo do documento;
- Data do ato;
- Data da publicação;
- Descrição;
- Arquivo PDF opcional.

### Ações

- Novo;
- Salvar;
- Alterar;
- Excluir;
- Consultar.

---

## 20. Tela 14 — Orçamentário / Alteração Orçamentária

### Objetivo

Permitir cadastro de alteração orçamentária.

### Campos

- Lei previamente cadastrada;
- Decreto autorizador;
- Data do ato;
- Data da publicação;
- Tipo do ato;
- Tipo de crédito;
- Tipo de recurso;
- Valor do crédito.

### Tipos de ato

- Decreto;
- Resolução;
- Ato Gestor.

### Tipos de crédito

- Especial;
- Suplementar;
- Extraordinário.

### Tipos de recurso

- Superávit;
- Excesso de arrecadação.

### Ações na listagem

- Alterar;
- Excluir;
- Alterações;
- Imprimir;
- Informações.

---

## 21. Tela 15 — Alterações em Dotações

### Objetivo

Permitir indicar quais dotações receberão alterações.

### Campos

- Dotação orçamentária;
- Conta de receita;
- Valor suprimido;
- Valor suplementado;
- Saldo atual;
- Novo saldo.

### Ações

- Adicionar dotação;
- Validar saldo;
- Salvar alteração;
- Visualizar saldo;
- Gerar resumo.

---

## 22. Tela 16 — Impressão / PDF da Alteração Orçamentária

### Objetivo

Demonstrar geração de resumo em PDF.

### Conteúdo do PDF

- Dados da lei;
- Dados do decreto;
- Tipo de crédito;
- Tipo de recurso;
- Valor;
- Dotações alteradas;
- Assinatura/rodapé institucional.

Para a POC, pode ser uma tela de preview com botão “Gerar PDF”.

---

## 23. Tela 17 — Demonstração Técnica

### Objetivo

Provar que o sistema segue boas práticas e arquitetura organizada.

### Conteúdo da tela

Criar uma página chamada “Demonstração Técnica” mostrando:

- Estrutura de pastas do frontend;
- Separação entre pages, components, services, types e hooks;
- Comunicação com API;
- Componentes reutilizáveis;
- Exemplo de Model/View/Controller no backend;
- Compatibilidade com Chrome, Firefox e Safari;
- Uso de design system.

Essa tela ajuda a responder diretamente aos requisitos técnicos iniciais do anexo.

---

## 24. Componentes shadcn/ui recomendados

Usar fortemente:

- Button;
- Card;
- Input;
- Label;
- Select;
- Textarea;
- Badge;
- Table;
- Tabs;
- Dialog;
- AlertDialog;
- DropdownMenu;
- Breadcrumb;
- Separator;
- ScrollArea;
- Toast/Sonner;
- Progress;
- Tooltip;
- Calendar/DatePicker.

---

## 25. Status globais do sistema

### Solicitação de pagamento

```txt
Pendente
Aguardando Aprovação dos Anexos
Anexos Recusados
Aguardando Autorização do Gestor
Em Liquidação
Em Ordem de Pagamento
Pagamento em Remessa
Pagamento Realizado
Cancelada
```

### Anexos

```txt
Pendente
Anexo Cadastrado
Aguardando Aprovação
Aprovado
Recusado
```

### Chamado

```txt
Aberto
Em Atendimento
Concluído
```

### Empenho

```txt
Disponível
Bloqueado
Sem Saldo
```

---

## 26. Dados mockados mínimos

### Fornecedor

```ts
{
  id: 1,
  nome: 'Fornecedor Demonstração LTDA',
  cnpj: '12.345.678/0001-90',
  responsavelTecnico: 'Responsável Técnico Demonstração',
  cpf: '123.456.789-00'
}
```

### Empenho

```ts
{
  id: 1,
  numero: '934/2023',
  contrato: 'Contrato 154/2023',
  dataEmissao: '01/02/2023',
  valor: 150000,
  saldo: 45000,
  status: 'Disponível'
}
```

### Solicitação

```ts
{
  id: 1,
  numero: '14568/2026',
  valor: 12000,
  status: 'Pendente',
  documentoFiscal: 'NF 12345',
  solicitante: 'Responsável Técnico Demonstração'
}
```

---

## 27. APIs que o backend deve priorizar

### Autenticação

```txt
POST /api/auth/login
GET /api/auth/me
```

### Portal do Fornecedor

```txt
GET /api/fornecedor/empenhos
GET /api/fornecedor/empenhos/:id
```

### Solicitações

```txt
GET /api/empenhos/:id/solicitacoes
POST /api/empenhos/:id/solicitacoes
GET /api/solicitacoes/:id
POST /api/solicitacoes/:id/cancelar
GET /api/solicitacoes/:id/tramites
```

### Anexos

```txt
GET /api/solicitacoes/:id/anexos
POST /api/solicitacoes/:id/anexos
POST /api/anexos/:id/aprovar
POST /api/anexos/:id/recusar
DELETE /api/anexos/:id
```

### Suporte

```txt
GET /api/chamados
POST /api/chamados
GET /api/chamados/:id
POST /api/chamados/:id/responder
POST /api/chamados/:id/anexos
POST /api/chamados/:id/concluir
```

### Prestação de Contas

```txt
GET /api/prestacao-contas/arquivos
POST /api/prestacao-contas/exportar
GET /api/prestacao-contas/exportacoes
GET /api/prestacao-contas/exportacoes/:id/download
```

### Orçamentário

```txt
GET /api/orcamentario/leis-atos
POST /api/orcamentario/leis-atos
GET /api/orcamentario/alteracoes
POST /api/orcamentario/alteracoes
POST /api/orcamentario/alteracoes/:id/dotacoes
GET /api/orcamentario/alteracoes/:id/pdf
```

---

## 28. Ordem de desenvolvimento recomendada

### Dia 1 — Base e Fluxo Principal

1. Criar projeto Next.js;
2. Configurar Tailwind e shadcn/ui;
3. Criar layout base;
4. Criar login fake;
5. Criar sidebar;
6. Criar listagem de empenhos;
7. Criar listagem de solicitações;
8. Criar formulário de nova solicitação.

### Dia 2 — Anexos e Andamento

1. Criar tela de anexos;
2. Criar upload visual;
3. Criar aprovação/recusa de anexos;
4. Criar cancelamento;
5. Criar tela de informações;
6. Criar stepper de andamento;
7. Criar abas da solicitação.

### Dia 3 — Suporte e Exportador

1. Criar botão global de suporte;
2. Criar novo chamado;
3. Criar listagem de chamados;
4. Criar detalhe/log do chamado;
5. Criar exportador SIM-AM;
6. Criar listagem de arquivos gerados.

### Dia 4 — Orçamentário e Polimento

1. Criar cadastro de leis e atos;
2. Criar alteração orçamentária;
3. Criar tela de dotações;
4. Criar preview PDF;
5. Criar demonstração técnica;
6. Testar fluxo completo.

### Antes da POC

1. Testar Chrome;
2. Testar Firefox;
3. Testar Safari, se possível;
4. Preparar roteiro de apresentação;
5. Preparar dados fixos;
6. Ensaiar fluxo com cronômetro;
7. Garantir que nenhum botão importante fique sem resposta.

---

## 29. Roteiro de apresentação na POC

### Parte 1 — Ambiente e Arquitetura

1. Abrir aplicação;
2. Mostrar compatibilidade no navegador;
3. Mostrar tela de Demonstração Técnica;
4. Explicar separação frontend/backend;
5. Mostrar estrutura MVC do backend.

### Parte 2 — Portal do Fornecedor

1. Login com CPF;
2. Listar empenhos;
3. Acessar solicitação de pagamento;
4. Criar nova solicitação;
5. Preencher dados fiscais e bancários;
6. Enviar anexos;
7. Mostrar status “Aguardando Aprovação”.

### Parte 3 — Gestor

1. Trocar perfil para Gestor do Contrato;
2. Abrir solicitação;
3. Aprovar um anexo;
4. Recusar outro com motivo;
5. Mostrar histórico atualizado.

### Parte 4 — Responsável Técnico

1. Voltar ao perfil Responsável Técnico;
2. Ver motivo da recusa;
3. Substituir anexo;
4. Reenviar;
5. Consultar andamento.

### Parte 5 — Cancelamento

1. Abrir solicitação pendente;
2. Cancelar com motivo;
3. Mostrar saldo liberado;
4. Mostrar histórico do cancelamento.

### Parte 6 — Suporte

1. Clicar no ícone global de suporte;
2. Abrir chamado;
3. Inserir anexo;
4. Consultar chamado;
5. Responder chamado em andamento;
6. Mostrar visão de gestor consultando todos.

### Parte 7 — Prestação de Contas

1. Acessar exportador;
2. Selecionar ano, módulo, tipo e mês;
3. Selecionar arquivo;
4. Exportar;
5. Mostrar arquivo gerado;
6. Clicar em baixar.

### Parte 8 — Orçamentário

1. Cadastrar lei/ato;
2. Criar alteração orçamentária;
3. Inserir tipo de crédito e recurso;
4. Adicionar dotação;
5. Consultar informações;
6. Gerar PDF.

---

## 30. Prioridade absoluta para passar

Se o tempo apertar, priorizar nesta ordem:

1. Portal do Fornecedor;
2. Solicitação de Pagamento;
3. Anexos com aprovação/recusa;
4. Consulta de andamento com stepper e abas;
5. Suporte ao Usuário;
6. Exportador de Prestação de Contas;
7. Orçamentário;
8. Demonstração técnica.

O coração da POC é o fluxo de Solicitação de Pagamento. Ele é o mais detalhado e provavelmente será o mais cobrado.

---

## 31. Checklist final de conformidade

### Técnico

- [ ] Aplicação abre sem erro;
- [ ] Layout administrativo pronto;
- [ ] Compatível com Chrome;
- [ ] Compatível com Firefox;
- [ ] Sem dependência de plugin;
- [ ] Separação clara de componentes;
- [ ] API organizada;
- [ ] Tipagens criadas;
- [ ] Estados e status funcionando.

### Portal do Fornecedor

- [ ] Login por CPF;
- [ ] Validação de usuário inexistente;
- [ ] Listagem de empenhos;
- [ ] Nova solicitação;
- [ ] Dados fiscais;
- [ ] Dados bancários;
- [ ] Listagem de anexos;
- [ ] Upload visual;
- [ ] Aprovação;
- [ ] Recusa com motivo;
- [ ] Cancelamento com motivo;
- [ ] Histórico;
- [ ] Stepper de andamento;
- [ ] Abas de informações.

### Suporte

- [ ] Ícone global;
- [ ] Novo chamado;
- [ ] Anexo no chamado;
- [ ] Consulta de chamados;
- [ ] Log;
- [ ] Perfil usuário comum;
- [ ] Perfil gestor.

### Prestação de Contas

- [ ] Filtros de exportação;
- [ ] Seleção de arquivos;
- [ ] Exportação simulada/real;
- [ ] Arquivo gerado;
- [ ] Botão baixar.

### Orçamentário

- [ ] Cadastro de lei/ato;
- [ ] Cadastro de alteração;
- [ ] Tipos de crédito;
- [ ] Tipos de recurso;
- [ ] Dotações;
- [ ] Informações;
- [ ] PDF.

---

## 32. Recomendação final

A POC deve parecer simples, mas precisa ser extremamente objetiva. Não tentem inventar fluxo fora do edital. Construam exatamente o que o anexo pede, com dados bonitos, status claros, botões funcionando e histórico visível.

O avaliador precisa olhar e pensar:

> “Eles entenderam o fluxo da Prefeitura e conseguem implementar isso no ambiente real.”

Esse é o objetivo.
