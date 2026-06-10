###############################################################################
# Frontend — Next.js 16 (App Router) — Multi-stage Build
###############################################################################

# ─── Estágio 1: deps ──────────────────────────────────────────────────────────
# Instala apenas as dependências de produção e desenvolvimento para o build.
FROM node:22-alpine AS deps

# libc6-compat é necessário para bindings nativos (sharp, etc.)
RUN apk add --no-cache libc6-compat

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

# ─── Estágio 2: builder ────────────────────────────────────────────────────────
# Gera o bundle otimizado de produção.
FROM node:22-alpine AS builder

WORKDIR /app

COPY --from=deps /app/node_modules ./node_modules
COPY . .

# Desabilita telemetria do Next.js em build
ENV NEXT_TELEMETRY_DISABLED=1

# A variável BACKEND_URL é usada internamente pelo proxy do Next.js
# O valor padrão aponta para o container "backend" da rede Docker.
# Pode ser sobrescrito via ARG/ENV no docker-compose.
ARG BACKEND_URL=http://backend:8000
ENV BACKEND_URL=${BACKEND_URL}

RUN npm run build

# ─── Estágio 3: runner ─────────────────────────────────────────────────────────
# Imagem final mínima — apenas o que é necessário para rodar o servidor Next.js.
FROM node:22-alpine AS runner

WORKDIR /app

ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1

# Usuário não-root para segurança
RUN addgroup --system --gid 1001 nodejs \
 && adduser  --system --uid 1001 nextjs

# Copia o output standalone gerado pelo Next.js
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static   ./.next/static
COPY --from=builder --chown=nextjs:nodejs /app/public          ./public

USER nextjs

EXPOSE 3000

ENV PORT=3000
ENV HOSTNAME=0.0.0.0

CMD ["node", "server.js"]
