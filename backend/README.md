# 🖥️ Backend (BFF API) — Catálogo de Veículos

Este diretório contém o **BFF (Backend for Frontend)** do projeto **Vehicle Crawler**, desenvolvido em Laravel. Ele atua como intermediário entre a base de dados relacional (PostgreSQL) populada pelo crawler e a interface web (Next.js), fornecendo APIs rápidas, filtradas e estruturadas.

---

## 🛠️ Tecnologias e Dependências

* **PHP 8.3+**
* **Laravel 13 Framework**
* **PostgreSQL**: Banco relacional principal (onde os veículos transformados e seus históricos de preços são persistidos).

---

## 🔍 Endpoints da API

A API expõe rotas sob o prefixo `/api` para atender às necessidades do catálogo:

### 1. Listar/Buscar Veículos
* **Rota**: `GET /api/vehicles`
* **Descrição**: Retorna uma listagem paginada e filtrável de veículos.
* **Filtros Suportados (via query params)**:
  * `search`: Termo geral de busca (pesquisa insensível a maiúsculas/minúsculas no título, modelo e marca).
  * `brands[]`: Array de marcas (ex: `Chevrolet`, `Fiat`).
  * `model`: Busca aproximada por modelo do carro (ex: `Civic`).
  * `sources[]`: Array de portais de origem (ex: `mobiauto`).
  * `min_price` / `max_price`: Filtro por intervalo de preço.
  * `min_km` / `max_km`: Filtro por intervalo de quilometragem.
  * `min_year` / `max_year`: Filtro por ano de fabricação/modelo.
  * `order_by` / `order_direction`: Ordenação dinâmica.
  * `per_page`: Quantidade de registros por página.

### 2. Detalhes de um Veículo
* **Rota**: `GET /api/vehicles/{id}`
* **Descrição**: Detalha as especificações técnicas de um veículo específico e anexa o relacionamento `priceHistories`, expondo o histórico de variação de preços ao longo do tempo.

### 3. Metadados para Filtros Dinâmicos
* **Rota**: `GET /api/filters/metadata`
* **Descrição**: Fornece os valores dinâmicos de marcas de carros ativas, fontes (portais) de origem e os valores mínimos e máximos presentes na base (preço, quilometragem, ano). Utilizado pelo frontend para renderizar sliders e checkboxes de filtros automáticos sem hardcoding.

---

## 📁 Estrutura de Código Relevante

* **`App\Services\Catalog\VehicleSearchService`**: Centraliza a lógica de filtragem dinâmica utilizando query builders do Eloquent de forma limpa e otimizada.
* **`App\DTO\VehicleDTO`**: Garante a consistência dos dados que saem da API para o frontend, serializando os campos do banco nos formatos esperados (como arrays de imagem e timestamps formatados).
* **`App\Http\Responses\ApiResponse`**: Helper unificado de formatação de respostas HTTP JSON.

---

## 🚀 Como Executar

### Pré-requisitos
A base de dados PostgreSQL deve estar ativa e populada (as migrações de tabelas são compartilhadas ou executadas no setup inicial do docker-compose).

### Inicialização Manual (Sem Docker)
1. Instale as dependências:
   ```bash
   composer install
   ```
2. Configure o arquivo de ambiente:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Execute o servidor de desenvolvimento:
   ```bash
   php artisan serve
   ```

*Nota: Em ambiente de desenvolvimento local usando o Docker Compose na raiz do repositório, o serviço `backend` roda automaticamente na porta `8080` (mapeada para a porta interna `8000`).*
