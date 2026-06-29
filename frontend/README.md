# 💻 Frontend (AutoCatálogo) — Interface Web

Este diretório contém a interface do usuário (Frontend) do projeto **Vehicle Crawler**, construída como um catálogo de veículos moderno, rápido e responsivo. É uma aplicação desenvolvida com **React 19** e **Next.js 16**.

---

## 🛠️ Tecnologias e Bibliotecas

* **Next.js 16 (App Router)** & **React 19**: Estrutura moderna baseada em componentes, renderização otimizada e Client Components (`"use client"`) para alta interatividade.
* **Zustand (v5)**: Gerenciamento de estado global no lado do cliente (controle unificado do termo de busca, filtros de marcas/fontes, intervalos numéricos de preço/ano/KM, ordenação e página atual). Utiliza `useShallow` para evitar re-renderizações desnecessárias.
* **React Query / @tanstack/react-query (v5)**: Gerenciamento e cacheamento do estado de rede. Garante paginação e filtragem fluidas, com transições sem flicker (utilizando `placeholderData` para reter os resultados anteriores durante buscas de background).
* **Axios (v1.18)**: Cliente HTTP configurado para se comunicar com as rotas do BFF.
* **Tailwind CSS (v4)**: Framework utilitário de estilização para uma interface de alta fidelidade visual, com suporte nativo a modo escuro/claro e design responsivo.
* **Embla Carousel (v8)**: Carrossel leve e flexível com suporte a transições suaves e autoplay, utilizado para exibir a galeria de imagens de cada anúncio.
* **Lucide React**: Biblioteca de ícones vetoriais modernos.

---

## 🔍 Comunicação com o BFF (Rewrites/Proxy)

Para contornar problemas de CORS (Cross-Origin Resource Sharing) em ambiente de desenvolvimento local, o Next.js está configurado (`next.config.ts`) para reescrever as requisições destinadas ao prefixo `/api/v1` diretamente para a URL do backend:

```typescript
// next.config.ts
async rewrites() {
  return [
    {
      source: "/api/v1/:path*",
      destination: `${backendUrl}/api/:path*`,
    },
  ];
}
```

O `backendUrl` é resolvido através da variável de ambiente `BACKEND_URL`, apontando para a API do Laravel.

---

## 📁 Estrutura de Componentes

* **`components/catalog-page.tsx`**: O layout principal do catálogo que integra o cabeçalho, barra de busca, painel de filtros e grid de listagem.
* **`components/filters/filter-sidebar.tsx`**: Painel lateral que busca dinamicamente os metadados do backend (`/api/filters/metadata`) para renderizar os intervalos de busca (preço, quilometragem, ano) e as opções de marcas/fontes sem valores hardcoded.
* **`components/vehicles/vehicle-grid.tsx`**: Grid inteligente que lida com estados de carregamento (exibindo skeletons de carregamento), erros de comunicação ou telas de resultados vazios.
* **`components/vehicles/vehicle-detail-dialog.tsx`**: Modal de detalhamento contendo informações técnicas detalhadas (carroceria, combustível, portas, câmbio), carrossel de fotos do veículo e o histórico de preços.

---

## 🚀 Como Executar

### Pré-requisitos
Certifique-se de que a API BFF (backend) esteja de pé para responder às requisições do catálogo.

### Inicialização Manual (Sem Docker)
1. Instale as dependências:
   ```bash
   npm install
   ```
2. Crie/Configure a variável de ambiente:
   Crie um arquivo `.env` na raiz do diretório frontend informando o endereço do BFF:
   ```env
   BACKEND_URL=http://localhost:8000
   ```
3. Rode o servidor Next.js em modo de desenvolvimento:
   ```bash
   npm run dev
   ```
4. Acesse a aplicação no navegador em `http://localhost:3000`.

---

## 📦 Build de Produção

Para gerar o build estático otimizado e rodar a aplicação em produção:

```bash
npm run build
npm run start
```
