import http from 'node:http';

const host = process.env.WORKER_HOST || '127.0.0.1';
const port = Number(process.env.WORKER_PORT || 31181);
if (!Number.isInteger(port) || port < 1 || port > 65535) {
  throw new Error('Invalid WORKER_PORT');
}

const server = http.createServer((request, response) => {
  if (request.url !== '/health' || request.method !== 'GET') {
    response.writeHead(404, { 'content-type': 'application/json' });
    response.end(JSON.stringify({ error: 'not_found' }));
    return;
  }
  response.writeHead(200, {
    'content-type': 'application/json',
    'cache-control': 'no-store'
  });
  response.end(JSON.stringify({
    status: 'ok',
    service: 'church-center-worker',
    whatsapp: 'not_configured',
    uptime_seconds: Math.round(process.uptime())
  }));
});

server.listen(port, host, () => {
  console.log(`Church Center worker listening on http://${host}:${port}`);
});

function shutdown(signal) {
  console.log(`Received ${signal}, stopping worker`);
  server.close(() => process.exit(0));
  setTimeout(() => process.exit(1), 10000).unref();
}

process.on('SIGINT', () => shutdown('SIGINT'));
process.on('SIGTERM', () => shutdown('SIGTERM'));
