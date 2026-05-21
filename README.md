# BFF (Backend for Frontend)

Serviço responsável por centralizar e orquestrar as chamadas aos microsserviços internos (`soat-upload-service` e `soat-fase5-report`), expondo uma API unificada para o cliente.


## Alunos

| Aluno | RM | Discord | LinkedIn |
|---|---|---|---|
| Felipe | 365154 | felipeoli7eira | [@felipeoli7eira](https://www.linkedin.com/in/felipeoli7eira) |
| Nicolas | 365746 | nic_hcm | [@Nicolas Martins](https://www.linkedin.com/in/nicolas-hcm) |
| William | 365973 | wllsistemas | [@William Francisco Leite](https://www.linkedin.com/in/williamfranciscoleite) |
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
