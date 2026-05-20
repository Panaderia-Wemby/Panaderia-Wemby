import 'dotenv/config';
import { createGrpcClients, startGrpcServer } from './grpc.js';
import { createHttpApp } from './http.js';
import { initializeStore } from './store.js';

const grpcPort = Number(process.env.GRPC_PORT || 50051);
const httpPort = Number(process.env.HTTP_PORT || 8080);

async function main() {
  await initializeStore();
  await startGrpcServer(grpcPort);
  const clients = createGrpcClients(`localhost:${grpcPort}`);
  const app = createHttpApp(clients);

  app.listen(httpPort, () => {
    console.log(`HTTP API listening on ${httpPort}`);
    console.log(`gRPC listening on ${grpcPort}`);
  });
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});