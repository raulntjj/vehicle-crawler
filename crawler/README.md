# 🛠️ Crawler de Veículos — Documentação de Arquitetura e Funcionamento

Este documento detalha o funcionamento técnico interno do módulo **Crawler** do projeto **Vehicle Crawler**, explicando o design de scraping, a arquitetura baseada em drivers e o pipeline de Processamento de Dados (ETL) de ponta a ponta.

---

## 🗺️ Arquitetura Geral do Sistema

O sistema é orientado a eventos e baseado em filas de mensageria (RabbitMQ) para desacoplar a extração (scraping) do processamento e persistência das informações.

![Arquitetura do Pipeline](./docs/event-driven-etl-architecture.svg)

### Fluxo Macroscópico (ETL)

1. **Trigger (Gatilho)**: O comando Artisan `php artisan crawl:vehicles` inicia o processo. Ele lê as marcas ativas no banco PostgreSQL (`BrandRepository`) e as localidades mapeadas na configuração do crawler. Em seguida, agenda tarefas individuais na fila do RabbitMQ (`portals.crawl`) para cada combinação de **`Portal × Localidade × Marca`**.
2. **Extract (Extração & Scraping)**: O Worker consome o job `CrawlVehicles`, que usa o `CrawlerManager` para instanciar o driver correspondente (ex: `MobiautoCrawler`). O HTML da página é obtido via requisição HTTP, os dados brutos estruturados em JSON são extraídos e salvos de forma imutável (Staging Area) na collection `raw_vehicles` do **MongoDB**. Se os dados são novos ou foram alterados (validado via hash MD5), um job `ProcessVehicles` é enviado para a fila `vehicles.process`.
3. **Transform (Transformação)**: O job `ProcessVehicles` consome a fila, recupera os dados brutos do MongoDB e os envia para o `VehicleTransformer`, que limpa e formata os dados (conversão de preços e quilometragem para tipos numéricos, limpeza de títulos, e separação de ano de fabricação/modelo).
4. **Load (Carga & Histórico de Preços)**: Os dados limpos são salvos no **PostgreSQL** por meio do `VehicleRepository`. Se for um veículo novo, cria-se o registro na tabela `vehicles` e insere o primeiro registro correspondente na tabela `price_histories`. Se já existir no banco, atualiza as informações cadastrais e adiciona um novo registro de preço na tabela `price_histories` caso o valor atualizado divirja do anterior.

---

## 🔍 O Crawler em Detalhes (Mecanismo de Scraping)

Diferente de crawlers convencionais que dependem de renderização DOM pesada ou navegação simulada em browsers headless (como Puppeteer ou Selenium), o crawler do projeto foi projetado para ser **extremamente leve, performático e resiliente a quebras de layout**.

### 1. Arquitetura Baseada em Drivers (Manager Pattern)
O sistema utiliza o padrão **Manager (Driver)** do Laravel para garantir flexibilidade e facilitar a integração de novos portais de veículos.

* **`CrawlerManager`**: Atua como o resolvedor de drivers. Ele mapeia identificadores textuais (ex: `'mobiauto'`) para suas respectivas classes concretas, resolvendo-as por meio do container de injeção de dependência do Laravel.
* **`VehicleCrawlerInterface`**: O contrato que todas as implementações de scraping devem seguir. Ela define os métodos:
  * `crawl(string $keyword, ?string $location): array`: Executa a extração baseada na palavra-chave (marca) e localidade, retornando uma lista de objetos `RawVehicleData`.
  * `getSource(): string`: Retorna a identificação única do portal de origem.

Para adicionar um novo portal, basta criar uma classe em `app/Services/Crawlers/Drivers/`, implementar o contrato `VehicleCrawlerInterface` e registrá-la no array `$drivers` do `CrawlerManager`.

### 2. Mecanismo de Extração Otimizada (Exemplo: Mobiauto)
O driver `MobiautoCrawler` utiliza um atalho de engenharia reversa para coletar os dados diretamente do estado de hidratação do Next.js do portal, em vez de realizar parsing complexo de elementos HTML.

1. **Construção de URL Dinâmica**:
   O crawler normaliza a marca (convertendo para lowercase e gerando um slug) e usa a localidade fornecida (ex: `sp-sao-paulo`) para compor a URL alvo:
   `https://www.mobiauto.com.br/comprar/carros/{location}/{brand}`
2. **Requisição HTTP Otimizada**:
   Realiza uma requisição HTTP GET utilizando headers customizados simulando um navegador real (`User-Agent`, `Accept-Language`, etc.) e um timeout definido de 15 segundos para evitar bloqueios ou travamento de workers.
3. **Extração do Estado Hydrated (`__NEXT_DATA__`)**:
   Em vez de ler tabelas ou cartões visuais da página, o crawler busca a tag `<script id="__NEXT_DATA__" type="application/json">` no HTML retornado usando expressões regulares:
   ```php
   preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/', $html, $matches)
   ```
   Esta tag contém o payload JSON exato que o Next.js usaria para renderizar a interface no lado do cliente. Decodificar esse JSON é muito mais rápido, consome menos memória e é imune a mudanças no design visual (classes CSS ou tags HTML) do portal.
4. **Mapeamento e Normalização (Data Mapping)**:
   Os registros brutos de anúncios (`$json['props']['pageProps']['deals']['results']`) são convertidos para o DTO `RawVehicleData` preenchendo as seguintes propriedades:
   * **Identificação**: O ID do anúncio no portal de origem é mapeado como `external_id`.
   * **Título do Anúncio**: Construído combinando marca, modelo e versão (`trim.make.name`, `trim.model.name` e `trim.name`).
   * **Links Canônicos**: A URL do anúncio original é reconstruída de forma consistente com o padrão do portal:
     `https://www.mobiauto.com.br/comprar/carros/{state}-{city}/{make}/{model}/{year}/{version}/detalhes/{id}?page=detail`
   * **Mapeamento de Mídia**: As imagens do veículo são ordenadas de acordo com a posição retornada e suas URLs de CDN são formadas com o parâmetro `imageId`:
     `https://image1.mobiauto.com.br/images/api/images/v1.0/{imageId}/transform/fl_progressive,f_webp,q_70,w_800`
   * **Atributos Técnicos**: Coleta dados de portas, tipo de carroceria (`bodystyle`), combustível (`fuel`) e transmissão (`transmission`).

---

## 💾 Staging Area (MongoDB) & Controle de Alterações (Deduplicação)

Antes de encaminhar os dados para o banco relacional, os veículos extraídos são persistidos no **MongoDB** através do `RawVehicleRepository`. O MongoDB funciona como uma **Staging Area / Data Lake** devido à sua alta performance de gravação e flexibilidade para armazenar payloads JSON estruturados imutáveis.

### Detecção de Mudanças (Change Data Capture - CDC)
Para economizar recursos de processamento e evitar updates redundantes no PostgreSQL, o job `CrawlVehicles` implementa uma lógica rigorosa de hash e controle de status:

1. **Geração de Assinatura (MD5 Hash)**:
   Gera-se uma hash MD5 a partir de todo o payload do veículo bruto:
   ```php
   $hash = md5(json_encode($rawData));
   ```
2. **Deduplicação Inteligente**:
   * O crawler consulta o MongoDB em busca de registros com a mesma chave composta `external_id` + `source`.
   * **Ignorar Reprocessamento**: Se o hash gerado for idêntico ao já armazenado e o status de processamento for `processed` ou `pending`, o veículo é ignorado. Nenhuma ação adicional é executada.
   * **Reprocessamento por Alteração**: Se o hash diferir (ex: o preço ou quilometragem mudou) ou se o processamento anterior falhou, o registro no MongoDB é atualizado, o status é resetado para `pending` e um novo job `ProcessVehicles` é enfileirado no RabbitMQ.
   * **Inserção de Novo Registro**: Caso o veículo não exista no MongoDB, ele é salvo com status `pending` e o processamento é disparado.

---

## ⚙️ Configurações e Customizações

As configurações do crawler residem no arquivo `config/crawler.php`. Nele, é possível parametrizar:

* **`brands`**: Lista de marcas padrões do mercado brasileiro que o sistema usará para busca caso nenhuma palavra-chave específica seja informada.
* **`delay_between_brands`**: Intervalo de espera (em segundos) entre as buscas de diferentes marcas. Essencial para evitar bloqueios por IP (Rate Limit/WAF) no servidor de destino.
* **`default_locations`**: Slugs no padrão `uf-cidade` (ex: `sp-sao-paulo`, `mg-belo-horizonte`) especificando as regiões de busca para os anúncios.

---

## 🚀 Como Executar o Crawler

### Pré-requisitos
Certifique-se de que os containers do **RabbitMQ**, **MongoDB** e **PostgreSQL** estejam rodando através do Docker Compose principal no diretório raiz do projeto.

### 1. Disparar a Extração
Você pode agendar a extração manualmente executando o comando Artisan:

```bash
# Executa para todos os portais e marcas cadastrados e ativos
php artisan crawl:vehicles

# Executa para um portal específico (ex: mobiauto)
php artisan crawl:vehicles mobiauto

# Executa para um portal e uma marca específica (ex: Chevrolet na Mobiauto)
php artisan crawl:vehicles mobiauto Chevrolet
```

### 2. Processamento Assíncrono (Workers)
O processamento das filas depende do consumo dos jobs no RabbitMQ. Você pode iniciar os workers do Laravel com o comando:

```bash
# Consome ambas as filas (extração de portais e processamento ETL)
php artisan queue:work --queue=portals.crawl,vehicles.process
```

*Nota: Em ambientes de desenvolvimento e produção (como o container `worker` do nosso docker-compose), este consumo é gerenciado de forma automática e resiliente pelo **Supervisor**.*