# Projeto php-apache-ecs

## O desafio consiste em colocar a aplicação no ar na aws no serviço ECS em conjunto com o terraform.

### Teste em local

1) baixe o projeto com o git clone e utilize, no terminal, na raiz do projeto o comando:
docker compose up -d --build

2) Acesse o navegador no enderço http://localhost:8080
. Verá a mensagem "Failed database connection..." significando que não há conexão com o banco de dados. 

3) Note que o arquivo .env no diretório ./src contém as informações do banco de dados necessárias para a conexão. Caso as informações do banco não estejam corretas, a conexão falhará. Anteção à elas.

Sua tarefa, portanto, é subir o serviço RDS na AWS com toda a configuração do ambiente e dos elementos de rede para acessar a aplicação através do serviço ECS.



