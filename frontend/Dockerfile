# Stage 1: Build the Vue application
FROM node:20-alpine AS build

WORKDIR /app

# Copy package manifests first for caching
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy source code
COPY . .

# Use .env or fallback to .env.example
RUN if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi

# Build the Vue application
RUN npm run build

# Stage 2: Serve the application with Nginx
FROM nginx:alpine

# Copy production build
COPY --from=build /app/dist /usr/share/nginx/html

# Copy custom Nginx configuration with API reverse proxy
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
