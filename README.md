# BFF (Backend for Frontend)

Serviço responsável por centralizar e orquestrar as chamadas aos microsserviços internos (`soat-upload-service` e `soat-fase5-report`), expondo uma API unificada para o cliente.


## Alunos

| Aluno | RM | Discord | LinkedIn |
|---|---|---|---|
| Felipe | 365154 | felipeoli7eira | [@felipeoli7eira](https://www.linkedin.com/in/felipeoli7eira) |
| Nicolas | 365746 | nic_hcm | [@Nicolas Martins](https://www.linkedin.com/in/nicolas-hcm) |
| William | 365973 | wllsistemas | [@William Francisco Leite](https://www.linkedin.com/in/williamfranciscoleite) |

---

## Descrição do Problema

O sistema tem como objetivo automatizar a análise de diagramas de arquitetura de software por meio de Inteligência Artificial. Equipes de engenharia submetem diagramas (JPG, JPEG, PNG ou PDF) e recebem, de forma assíncrona, um relatório com **componentes identificados**, **riscos** e **recomendações** de melhoria — eliminando a necessidade de revisão manual.

---

## Arquitetura Proposta

O sistema é composto por cinco microsserviços interligados:

| Serviço | Papel |
|---|---|
| **BFF** *(este serviço)* | Ponto de entrada unificado; orquestra chamadas aos serviços internos |
| **upload-service** | Recebe diagramas, armazena no Amazon S3, publica na fila `protocols` |
| **trigger-service** | Consome a fila, aciona a IA e persiste resultados no PostgreSQL |
| **report-service** | Consulta resultados e gera relatório em PDF |
| **RabbitMQ** | Broker de mensagens para comunicação assíncrona |

> Diagrama interativo: [FIAP - HACKATON FASE 5](https://www.tldraw.com/f/CPYtIC_xwtcfbSCH4vgkT?d=v567.4.3513.1667.page)

---

## Fluxo da Solução

**Envio para análise:**
1. Usuário envia diagrama via `POST /api/upload` neste **BFF**
2. BFF repassa ao **upload-service**, que armazena no Amazon S3
3. **upload-service** publica `{ protocol_uuid, file_url, ... }` na fila `protocols` do RabbitMQ
4. **trigger-service** consome a mensagem e aciona o **Analysis Service (IA)**
5. IA processa e publica o resultado na fila `analysis_response`
6. **trigger-service** persiste o resultado no PostgreSQL (`SUCESSO` ou `ERRO`)

**Consulta do resultado:**
1. Usuário consulta status via `GET /api/status/{uuid}` neste **BFF**
2. Usuário solicita relatório via `GET /api/report/{uuid}` neste **BFF**
3. **report-service** busca os dados no **trigger-service** e gera o PDF

---

## Setup via Docker Compose

A rede _default_ dos serviços é uma rede externa chamada `soat-net`. Crie-a antes de subir os containers:

```sh
docker network create soat-net
```

Em seguida, suba o serviço:

```sh
docker compose up -d
```

---

## Variáveis de Ambiente

| Variável | Descrição | Padrão |
|---|---|---|
| `UPLOAD_SERVICE_BASE_URL` | URL base do serviço de upload | — |
| `UPLOAD_SERVICE_TIMEOUT_SECONDS` | Timeout (em segundos) para chamadas ao serviço de upload | `10` |
| `REPORT_SERVICE_BASE_URL` | URL base do serviço de relatório | — |
| `REPORT_SERVICE_TIMEOUT_SECONDS` | Timeout (em segundos) para chamadas ao serviço de relatório | `10` |

---

## Endpoints

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/ping` | Health check do próprio BFF |
| `GET` | `/api/ping/upload` | Health check repassado ao serviço de upload |
| `GET` | `/api/ping/report` | Health check repassado ao serviço de relatório |
| `POST` | `/api/upload` | Envia um diagrama para análise (multipart/form-data, campo `diagram`) |
| `GET` | `/api/report/{uuid}` | Faz o download do relatório em PDF gerado para o protocolo informado |
| `GET` | `/api/report/{uuid}/status` | Consulta o status de processamento do relatório |
| `GET` | `/api/status/{uuid}` | Alias de `/api/report/{uuid}/status` |

### Upload — campo obrigatório

| Campo | Tipo | Regras |
|---|---|---|
| `diagram` | arquivo | JPG, JPEG, PNG ou PDF · máximo 2 MB |

---

## Testes

### Pré-requisitos

- Docker e Docker Compose
- Container `soat-bff` em execução (`docker compose up -d`)

### 1. Instalar dependências (incluindo dev)

```bash
docker exec soat-bff composer install
```

> Necessário apenas na primeira vez ou após alterações no `composer.json`.

### 2. Executar os testes

```bash
docker exec soat-bff vendor/bin/phpunit
```

### 3. Executar com relatório de cobertura HTML

```bash
docker exec soat-bff vendor/bin/phpunit --coverage-html var/coverage/html
```

O relatório estará disponível em `application/var/coverage/html/index.html`.

### Estrutura dos testes

| Suite   | Local                    | O que testa                              |
|---------|--------------------------|------------------------------------------|
| Feature | `tests/Feature/`         | Endpoints HTTP e integração com serviços externos (mockados via `Http::fake()`) |
