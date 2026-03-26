# BFF (Backend for frontend)

Pontos de atenção para setup via <b>docker-compose</b>:

É possível ver no arquivo [docker-compose.yaml](./docker-compose.yaml) que a rede _default_ dos serviços, é uma rede externa chamada "soat-net". É fundamental para o pleno funcionamento dos serviços, que essa rede externa exista. Por favor, crie uma rede chamada "soat-net" com o seguinte comando:
```sh
docker network create soat-net
```
