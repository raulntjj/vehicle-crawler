# 🛠️ Pipeline ETL — Fluxo de Funcionamento (Crawler ao Banco)

Este documento descreve detalhadamente a arquitetura de dados e o fluxo de funcionamento de ponta a ponta do projeto **Vehicle Crawler**, abrangendo as etapas de **Gatilho (Trigger)**, **Extração (Extract)**, **Transformação (Transform)** e **Carga (Load)**.

---

## 🗺️ Arquitetura do Sistema

![Arquitetura do Pipeline](./docs/event-driven-etl-architecture.svg)

## 🔍 Descrição Detalhada das Etapas

### 1. Inicialização & Definição de Escopo (Trigger)
* O processo começa quando o comando Artisan `php artisan crawl:vehicles` é acionado manualmente ou via cron (Task Scheduler).
* O comando lê as marcas ativas do banco PostgreSQL (`BrandRepository`) e as localidades mapeadas na configuração do crawler.
* O método `CrawlVehicles::dispatchForPortal` realiza a multiplicação de escopo: cria uma tarefa individual na fila do RabbitMQ (`portals.crawl`) para cada combinação de **`Portal × Localidade × Marca`**.

### 2. Extração & Staging Area (Extract / Scraping)
* O worker consome o job `CrawlVehicles` na fila `portals.crawl`.
* O `CrawlerManager` instancia o driver específico para o portal alvo (ex: `MobiautoCrawler`).
* É efetuada a requisição HTTP GET para coletar o HTML da página de anúncios do portal.
* O parser extrai o bloco de dados estruturados em JSON contido na tag de renderização do NextJS (`<script id="__NEXT_DATA__">`).
* Cada anúncio individual da resposta é convertido em um DTO (`RawVehicleData`) e persistido de forma imutável (JSON bruto) na collection `raw_vehicles` do **MongoDB (Data Lake / Staging Area)**.
* Ao salvar, o identificador do MongoDB (`_id` do documento, convertido em string como `$mongoId`) é capturado.
* O job finaliza despachando um novo job (`ProcessVehicles`) para a fila `vehicles.process` com a referência `$mongoId`.

### 3. Transformação & Higienização (Transform)
* O worker consome o job `ProcessVehicles` na fila `vehicles.process`.
* Ele recupera os dados brutos salvos no MongoDB utilizando o `$mongoId`.
* Esses dados brutos são passados ao `VehicleTransformer`, que executa as seguintes regras de negócios e limpeza:
  * **Título:** Remove espaços em branco redundantes.
  * **Preço:** Limpa caracteres monetários e converte para número de ponto flutuante (ex: `"R$ 74.900,00"` ➔ `74900.0`).
  * **Quilometragem (KM):** Remove texto "km" e pontuação para retornar um inteiro (ex: `"12.000 km"` ➔ `12000`).
  * **Anos:** Separa o ano de fabricação e modelo no formato correto de inteiros (ex: `"2020/2021"` ➔ `2020` e `2021`).

### 4. Carga & Histórico de Preços (Load)
* O `VehicleRepository` recebe a estrutura de dados higienizada e executa no **PostgreSQL**:
  * Verifica se o veículo já existe utilizando a chave única composta de `external_id` (ID que o veículo possui no site of origem) e `source` (portal de origem).
  * **Se for um veículo novo:** Cria o registro na tabela `vehicles` e insere o primeiro registro correspondente na tabela `price_histories`.
  * **Se já existir no banco:** Atualiza os dados cadastrais na tabela `vehicles`.
  * **Comparativo de Preço:** Se o preço atualizado for diferente do preço que estava salvo anteriormente no banco, um novo registro contendo o novo valor e o timestamp atual é inserido na tabela `price_histories`.