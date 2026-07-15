# php-apache-ecs

Aplicação PHP mínima, empacotada em Docker (PHP 8.3 + Apache), usada como base para um **desafio prático de DevOps**: publicar a imagem no Docker Hub e colocar a aplicação no ar na AWS usando **ECS**, com o banco de dados rodando em **RDS (PostgreSQL)**.

A aplicação em si é intencionalmente simples: ela só tenta abrir uma conexão PDO com um banco PostgreSQL e imprime na tela se a conexão teve sucesso ou falhou. O foco do desafio não é o código PHP, e sim a infraestrutura necessária para essa aplicação rodar de forma segura e acessível na nuvem.

## Sumário

- [Stack e estrutura do projeto](#stack-e-estrutura-do-projeto)
- [Como funciona a aplicação](#como-funciona-a-aplicação)
- [Rodando localmente](#rodando-localmente)
- [Variáveis de ambiente](#variáveis-de-ambiente)
- [O desafio: subir na AWS](#o-desafio-subir-na-aws)
- [Critérios de aceite](#critérios-de-aceite)
- [Dicas e pegadinhas](#dicas-e-pegadinhas)

## Stack e estrutura do projeto

- **Linguagem/Runtime:** PHP 8.3 rodando sobre Apache (`mod_php`), imagem base `php:8.3-apache`.
- **Banco de dados:** PostgreSQL, acessado via `PDO` (extensão `pdo_pgsql`).
- **Dependências PHP:** gerenciadas via Composer (`vlucas/phpdotenv`, `graze/guzzle-jsonrpc`).
- **Containerização:** Docker + Docker Compose para o ambiente local.

```
.
├── docker-compose.yaml          # orquestração local (build + porta 8080)
├── docker/
│   └── php/
│       ├── Dockerfile.local     # imagem usada pelo docker compose (dev)
│       └── Dockerfile.prod      # imagem de referência para produção (com OpenTelemetry)
└── src/
    ├── Dockerfile                # variante de imagem standalone (php:8.3-apache)
    ├── composer.json
    ├── .env_example               # modelo das variáveis de ambiente
    ├── .env                       # (não versionado) credenciais reais do banco
    ├── public/
    │   └── index.php              # ponto de entrada — testa a conexão com o banco
    └── Core/
        └── Database/
            └── Connection.php     # singleton PDO de conexão com o Postgres
```

O `DocumentRoot` do Apache é apontado para `public/`, então a aplicação só expõe o que está dentro dessa pasta.

## Como funciona a aplicação

`src/public/index.php` faz três coisas:

1. Carrega as variáveis de ambiente do arquivo `.env` (via `vlucas/phpdotenv`).
2. Chama `Core\Database\Connection::getInstance()`, que monta a DSN do PDO a partir de `DATABASE_HOST`, `DATABASE_NAME`, `DATABASE_USER` e `DATABASE_PASSWD` e tenta conectar no Postgres.
3. Renderiza uma página HTML com um card central exibindo um indicador de status: um botão verde **"Conexão Ativa"** se a conexão com o banco funcionou, ou vermelho **"Conexão Inativa"** se falhou.

Ou seja: **a página é literalmente um health-check visual da conexão com o banco.** Se o botão aparecer vermelho, é sinal de que a aplicação está rodando corretamente, mas não consegue enxergar o banco — o que normalmente aponta para credenciais erradas, banco fora do ar, ou problema de rede/security group entre o container e o RDS.

## Rodando localmente

Pré-requisitos: Docker e Docker Compose instalados.

```bash
git clone <url-do-repositorio>
cd php-apache-ecs
docker compose up -d --build
```

Acesse [http://localhost:8080](http://localhost:8080). Sem um `.env` válido apontando para um banco real, você verá o botão de status em vermelho, **"Conexão Inativa"** — isso é esperado nesse ponto.

Para testar a conexão de verdade localmente, suba um Postgres (por exemplo outro container) e preencha `src/.env` com os dados dele.

## Variáveis de ambiente

Definidas em `src/.env` (use `src/.env_example` como modelo — esse arquivo não é versionado):

| Variável          | Descrição                                  |
|-------------------|---------------------------------------------|
| `DATABASE_HOST`   | Host/endpoint do PostgreSQL                  |
| `DATABASE_NAME`   | Nome do banco de dados                       |
| `DATABASE_USER`   | Usuário do banco                             |
| `DATABASE_PASSWD` | Senha do banco                               |

Em produção (ECS), essas variáveis **não devem ir dentro da imagem nem do `.env` commitado** — a ideia é injetá-las via *task definition* do ECS (variáveis de ambiente ou, idealmente, **AWS Secrets Manager** / **SSM Parameter Store** para a senha).

## O desafio: subir na AWS

O objetivo é pegar essa aplicação e colocá-la no ar na AWS, com o banco gerenciado pelo RDS. O desafio se divide em duas grandes etapas:

### Etapa 1 — Publicar a imagem no Docker Hub

1. Construir a imagem de produção a partir de `docker/php/Dockerfile.prod` (ou `src/Dockerfile`, caso opte pela variante sem OpenTelemetry).
2. Criar um repositório no Docker Hub.
3. Taguear e publicar a imagem:
   ```bash
   docker build -t <seu-usuario>/php-apache-ecs:latest -f docker/php/Dockerfile.prod .
   docker push <seu-usuario>/php-apache-ecs:latest
   ```
4. Garantir que a imagem builda sem depender de arquivos locais sensíveis (o `.env` **não** deve ir para dentro da imagem).

### Etapa 2 — Provisionar a infraestrutura na AWS e rodar via ECS

Você deve provisionar (idealmente via Terraform, conforme mencionado no enunciado original do desafio) os seguintes componentes:

**Rede (setup simplificado — 1 AZ, sem NAT, sem Load Balancer)**
- VPC com **uma única subnet pública** em **uma única AZ** (sem necessidade de alta disponibilidade multi-AZ para esse desafio).
- Internet Gateway anexado à VPC, com rota `0.0.0.0/0` na route table da subnet pública.
- **Sem NAT Gateway**: como a task do ECS já roda na subnet pública com IP público atribuído, ela tem saída direta para a internet (pull de imagem do Docker Hub) através do próprio Internet Gateway.
- Security Groups:
  - Um para o serviço ECS, liberando a porta 80 de entrada (`0.0.0.0/0`, já que o acesso será pelo IP público da task).
  - Um para o RDS, liberando a porta 5432 **apenas** a partir do Security Group do ECS (nunca `0.0.0.0/0`).

**Banco de dados (RDS)**
- Instância RDS PostgreSQL **Single-AZ** (sem standby/réplica) — alinhado com a decisão de usar apenas uma AZ.
- DB Subnet Group posicionado na mesma subnet pública usada pelo ECS (sem subnet privada nesse desenho).
- Credenciais alinhadas com as variáveis `DATABASE_HOST`, `DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWD` que a aplicação espera.
- ⚠️ Atenção: a API da AWS exige que todo **DB Subnet Group** contenha subnets em **pelo menos 2 AZs diferentes**, mesmo para uma instância Single-AZ. Isso é uma restrição de plataforma do RDS, não uma escolha de redundância. Na prática, crie uma segunda subnet pública (pode ficar vazia, sem nenhum recurso rodando nela) só para satisfazer esse requisito do subnet group — a instância RDS em si continuará sendo provisionada em uma única AZ.

**Container / ECS**
- Repositório ECR é opcional aqui, já que a imagem vem do Docker Hub.
- Cluster ECS (Fargate é a opção mais simples, sem gerenciar EC2).
- Task Definition apontando para a imagem publicada no Docker Hub, com as variáveis de ambiente do banco configuradas (ou via Secrets Manager).
- Service ECS rodando na subnet pública, com **"Assign public IP" habilitado** — é esse IP público da ENI da task que será usado para acessar a aplicação. **Sem Load Balancer/ALB** nesse desenho.
- IAM Role de execução da task (`ecsTaskExecutionRole`) com permissões mínimas necessárias.

**Validação**
- Obter o IP público atribuído à task (console ECS → task → aba "Configuration" → Public IP, ou via `aws ecs describe-tasks` + `aws ec2 describe-network-interfaces`) e acessar `http://<ip-público-da-task>` para confirmar que o botão de status mudou de vermelho ("Conexão Inativa") para verde ("Conexão Ativa").

### Ordem sugerida de execução

1. VPC, subnet pública única (mais a subnet auxiliar exigida pelo DB Subnet Group), IGW, route table e Security Groups.
2. RDS PostgreSQL Single-AZ dentro da VPC.
3. Build + push da imagem para o Docker Hub.
4. Cluster ECS, Task Definition (com as env vars do banco) e Service com IP público habilitado.
5. Obter o IP público da task e testar o acesso direto pela porta 80.

## Critérios de aceite

- [ ] Imagem publicada em um repositório público (ou privado, com credenciais informadas) no Docker Hub.
- [ ] Aplicação acessível publicamente via IP público da task ECS na porta 80 (sem Load Balancer).
- [ ] Página exibe o botão de status verde ("Conexão Ativa"), confirmando conexão bem-sucedida com o RDS.
- [ ] RDS não exposto publicamente (Security Group restrito ao tráfego do ECS).
- [ ] Credenciais do banco não versionadas nem embutidas na imagem (injetadas via ECS/Secrets Manager).
- [ ] Infraestrutura provisionada em uma única AZ, sem NAT Gateway e sem Load Balancer.
- [ ] Infraestrutura reproduzível (idealmente como código, ex. Terraform).

## Dicas e pegadinhas

- O `DocumentRoot` do Apache aponta para `public/` — se a task subir mas retornar 403/404, verifique se a imagem foi construída a partir do `Dockerfile.prod`/`Dockerfile.local` correto (eles já fazem esse ajuste via `sed` no `apache2.conf`).
- `Dockerfile.prod` instala a extensão `opentelemetry` via PECL — o build pode demorar um pouco mais por causa da compilação.
- Lembre de garantir que o RDS e a task do ECS estejam na mesma VPC/subnet, senão a conexão vai dar timeout mesmo com credenciais certas.
- Nunca abra a porta 5432 do RDS para `0.0.0.0/0`; restrinja pelo Security Group do ECS.
- O IP público de uma task Fargate **não é fixo**: a cada novo deploy, restart ou substituição da task, um novo IP é atribuído. Para esse desafio isso é aceitável, mas é bom estar ciente ao testar (sempre confira o IP atual antes de acessar).
- Mesmo optando por uma única AZ para os recursos "ativos", o DB Subnet Group do RDS ainda exige 2 AZs cadastradas — veja a observação na seção de RDS acima.
