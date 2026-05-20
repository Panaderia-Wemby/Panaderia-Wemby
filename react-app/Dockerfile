FROM node:25-alpine AS build

WORKDIR /app
COPY react-app/package.json ./package.json
RUN npm install
COPY react-app .
RUN npm run build

FROM nginx:1.27-alpine
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80