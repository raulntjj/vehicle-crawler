FROM node:22-alpine

RUN apk add --no-cache libc6-compat

WORKDIR /app

EXPOSE 3000

ENV PORT=3000
ENV HOSTNAME=0.0.0.0
ENV NEXT_TELEMETRY_DISABLED=1
ENV NODE_ENV=development

CMD ["sh", "-c", "npm install && npm run dev"]
