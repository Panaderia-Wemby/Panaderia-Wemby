FROM node:25-alpine

WORKDIR /app

COPY backend/package.json ./package.json
RUN npm install --omit=dev

COPY backend/src ./src
COPY grpc ./grpc

EXPOSE 8080 50051

CMD ["node", "src/index.js"]