# BFF (Backend for frontend)

Pontos de atenção para setup via <b>docker-compose</b>:

É possível ver no arquivo [docker-compose.yaml](./docker-compose.yaml) que a rede _default_ dos serviços, é uma rede externa chamada "soat-net". É fundamental para o pleno funcionamento dos serviços, que essa rede externa exista. Por favor, crie uma rede chamada "soat-net" com o seguinte comando:
```sh
docker network create soat-net
```

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
