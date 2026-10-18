# ---------- Tahap 1: build Vue.js dengan Node.js ----------
# Versi image dasar eksplisit (bukan latest).
FROM node:22.12-alpine AS build

WORKDIR /app

# package.json & package-lock.json disalin lebih dulu agar cache layer efisien.
COPY package.json package-lock.json ./

RUN npm ci

COPY . .

# URL API untuk build produksi di dalam Docker Compose.
ARG VITE_API_BASE_URL=/api
ENV VITE_API_BASE_URL=${VITE_API_BASE_URL}
ARG VITE_ADMIN_EMAIL=admin@inventaris.test
ENV VITE_ADMIN_EMAIL=${VITE_ADMIN_EMAIL}

RUN npm run build

# ---------- Tahap 2: sajikan hasil build dengan Nginx ----------
FROM nginx:1.27-alpine

# Image final TIDAK berisi node_modules (hanya file statis hasil build).
COPY --from=build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]