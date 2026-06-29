# 🚗 Vehicle Crawler — Centralizador do Projeto

Este repositório contém a infraestrutura e os módulos do **Vehicle Crawler**, um sistema completo e multi-container voltado à extração automática de anúncios de veículos, processamento de dados (ETL), catalogação estruturada e exibição web responsiva.

O projeto é dividido em três aplicações principais que rodam sob uma arquitetura de microsserviços local orquestrada pelo **Docker Compose**.

---

## 📁 Estrutura do Repositório

O projeto pai é composto pelos seguintes diretórios:

* **[`crawler/`](file:///home/raulntjj/Repositories/vehicle-crawler/crawler/README.md)**: Aplicação CLI (Laravel) dedicada ao agendamento e execução de scrapers de veículos. Ela extrai os anúncios dos portais, realiza o controle de alterações e deduplica os dados no MongoDB.
* **[`backend/`](file:///home/raulntjj/Repositories/vehicle-crawler/backend/README.md)**: BFF REST API (Laravel) que serve de camada de acesso aos dados, expondo rotas rápidas e filtráveis para busca e detalhamento de anúncios persistidos no PostgreSQL.
* **[`frontend/`](file:///home/raulntjj/Repositories/vehicle-crawler/frontend/README.md)**: Painel web (Next.js 16/React 19) onde o catálogo é renderizado com filtros avançados dinâmicos e históricos de preços.

---

## 🗺️ Arquitetura do Sistema e Fluxo de Dados

A arquitetura do projeto é orientada a eventos e desacoplada em etapas de extração, transformação e carga (ETL):

![Arquitetura de Dados](./crawler/docs/event-driven-etl-architecture.svg)

1. **Agendamento (Maestro)**: O container `crawler` agenda ou inicia manualmente buscas para combinações de `Portal × Localidade × Marca` enviando jobs `CrawlVehicles` para a fila `portals.crawl` do RabbitMQ.
2. **Extração & Staging Area**: Os workers da fila `portals.crawl` buscam as páginas dos portais de destino (como a Mobiauto), obtêm os dados estruturados de hidratação do NextJS (`__NEXT_DATA__`) e salvam o payload JSON bruto no MongoDB (Staging Area).
3. **Change Data Capture (CDC)**: A hash MD5 do anúncio é comparada. Se o anúncio for novo ou se os dados tiverem sofrido alteração, o Worker envia uma tarefa de processamento para a fila `vehicles.process`.
4. **Transformação e Carga (ETL)**: O job `ProcessVehicles` consome a fila do RabbitMQ, recupera o anúncio do MongoDB, realiza a limpeza/transformação dos campos e persiste os dados consolidados no PostgreSQL. Caso o preço mude, um histórico é gravado na tabela de controle de preços.
5. **Consumo Web**: O Next.js solicita dados ao BFF (Backend) utilizando requisições HTTP proxied (rewrites) para evitar problemas de CORS, exibindo feeds rápidos com estados de cache integrados.

---

## 🛠️ Tecnologias do Ecossistema

### Bancos de Dados & Middleware:
* **PostgreSQL (v16)**: Banco relacional contendo as tabelas de veículos, marcas e o histórico consolidado de preços.
* **MongoDB (v7.0)**: Staging Area / Data Lake que armazena os registros originais imutáveis de anúncios brutos (JSON).
* **RabbitMQ**: Broker de mensagens responsável por gerenciar as filas de extração (`portals.crawl`) e de processamento ETL (`vehicles.process`).
* **Redis**: Camada de cache utilizada opcionalmente para otimizar as consultas da API.

---

## 🚀 Como Inicializar o Projeto (Docker Compose)

O projeto está totalmente configurado para subir todos os serviços locais de forma unificada através do Docker Compose.

### Passo 1: Configurar Variáveis de Ambiente
Copie o arquivo `.env.example` para `.env` nos diretórios do **crawler** e do **backend**:

```bash
cp crawler/.env.example crawler/.env
cp backend/.env.example backend/.env
```

### Passo 2: Subir a Infraestrutura e Serviços
Rode o comando do Compose no diretório raiz do projeto para fazer o build e iniciar todos os containers em segundo plano:

```bash
docker compose up --build -d
```

Este comando subirá os seguintes serviços:
* `postgres`, `mongodb`, `redis`, `rabbitmq` (Bancos e Middleware).
* `crawler` (Container de controle CLI).
* `worker` (Serviço executando Supervisor para processar as filas do RabbitMQ).
* `backend` (BFF API exposta localmente em `http://localhost:8080`).
* `frontend` (Dashboard Next.js exposto localmente em `http://localhost:3002`).

### Passo 3: Executar Migrações e Seeds de Marcas
Popule a base de dados PostgreSQL executando as migrations e seeders iniciais dentro do container do `crawler`:

```bash
docker compose exec crawler php artisan migrate --seed
```

O comando de seed é fundamental para cadastrar e ativar as marcas de veículos (definidas em `config/crawler.php`) no banco de dados.

### Passo 4: Rodar o Scraper Manualmente
Para testar a infraestrutura e rodar a carga inicial dos portais (extraindo e populando os bancos), execute o comando a partir do container `crawler`:

```bash
docker compose exec crawler php artisan crawl:vehicles
```

Acompanhe o processamento do pipeline monitorando os logs do container worker:
```bash
docker compose logs -f worker
```
