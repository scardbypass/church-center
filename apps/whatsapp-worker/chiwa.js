import http from 'node:http';
import { timingSafeEqual } from 'node:crypto';
import { mkdir } from 'node:fs/promises';
import { join } from 'node:path';
import QRCode from 'qrcode';
import makeWASocket, { DisconnectReason, useMultiFileAuthState, fetchLatestBaileysVersion } from '@whiskeysockets/baileys';

const host = process.env.WORKER_HOST || '127.0.0.1';
const port = Number(process.env.WORKER_PORT || 31181);
const secret = process.env.WORKER_API_TOKEN || '';
const sessionRoot = process.env.WORKER_SESSION_DIR || join(process.cwd(), '.sessions');
const connections = new Map();
const MAX_BODY = 12 * 1024;
const maxChurches = Number(process.env.WORKER_MAX_CHURCHES || 30);
const shuttingDown = { value: false };

if (!Number.isInteger(port) || port < 1 || port > 65535) throw Error('Invalid WORKER_PORT');
if (!secret || Buffer.byteLength(secret) < 32) throw Error('Set WORKER_API_TOKEN (minimum 32 bytes)');
if (host !== '127.0.0.1' && host !== '::1') throw Error('Bind worker to loopback only');

function json(res, status, data) {
  res.writeHead(status, { 'content-type': 'application/json', 'cache-control': 'no-store', 'x-content-type-options': 'nosniff' });
  res.end(JSON.stringify(data));
}
function authorized(req) {
  const received = req.headers.authorization || '';
  const expected = 'Bearer ' + secret;
  const a = Buffer.from(received);
  const b = Buffer.from(expected);
  return a.length === b.length && timingSafeEqual(a, b);
}
function validChurch(id) { return /^[1-9][0-9]{0,9}$/.test(String(id)); }
function state(id) {
  const c = connections.get(id);
  return c ? { church_id: id, status: c.status, qr_ready: Boolean(c.qr), last_error: c.lastError || null } : { church_id: id, status: 'disconnected', qr_ready: false };
}
async function connect(id) {
  if (!validChurch(id)) throw Error('invalid_church_id');
  if (connections.has(id)) return state(id);
  if (connections.size >= maxChurches) throw Error('worker_capacity_reached');
  const dir = join(sessionRoot, 'church-' + id);
  await mkdir(dir, { recursive: true, mode: 0o700 });
  const { state: auth, saveCreds } = await useMultiFileAuthState(dir);
  const { version } = await fetchLatestBaileysVersion();
  const entry = { status: 'connecting', qr: null, socket: null, lastError: null, retries: 0 };
  connections.set(id, entry);
  try {
    const socket = makeWASocket({ version, auth, printQRInTerminal: false, markOnlineOnConnect: false, syncFullHistory: false });
    entry.socket = socket;
    socket.ev.on('creds.update', saveCreds);
    socket.ev.on('connection.update', ({ connection, qr, lastDisconnect }) => {
      if (connections.get(id) !== entry) return;
      if (qr) { entry.qr = qr; entry.status = 'qr_ready'; }
      if (connection === 'open') { entry.status = 'connected'; entry.qr = null; entry.retries = 0; }
      if (connection === 'close') {
        entry.status = 'disconnected';
        entry.qr = null;
        const code = lastDisconnect?.error?.output?.statusCode;
        entry.lastError = String(code || 'connection_closed');
        connections.delete(id);
        if (!shuttingDown.value && code !== DisconnectReason.loggedOut) {
          const delay = Math.min(60000, 2000 * Math.pow(2, Math.min(entry.retries++, 5)));
          setTimeout(() => { if (!shuttingDown.value) connect(id).catch(console.error); }, delay).unref();
        }
      }
    });
    socket.ev.on('messages.upsert', ({ messages, type }) => {
      if (type !== 'notify') return;
      for (const message of messages || []) {
        if (message.key.fromMe || !message.message) continue;
        const chat = message.key.remoteJid || '';
        const body = message.message.conversation || message.message.extendedTextMessage?.text || '';
        if (/^\/?(menu|start)$/i.test(body.trim()) && !chat.endsWith('@g.us')) {
          socket.sendMessage(chat, { text: 'Selamat datang di Church Center. Hubungi pengurus gereja untuk informasi pelayanan.' }).catch(console.error);
        }
      }
    });
    return state(id);
  } catch (error) {
    connections.delete(id);
    throw error;
  }
}
async function readJson(req) {
  let size = 0; const chunks = [];
  for await (const chunk of req) {
    size += chunk.length;
    if (size > MAX_BODY) throw Error('body_too_large');
    chunks.push(chunk);
  }
  return JSON.parse(Buffer.concat(chunks).toString('utf8'));
}
const server = http.createServer(async (req, res) => {
  if (req.method === 'GET' && req.url === '/health') {
    return json(res, 200, { status: 'ok', service: 'church-center-worker', connected: [...connections.values()].filter(c => c.status === 'connected').length, uptime_seconds: Math.round(process.uptime()) });
  }
  if (!authorized(req)) return json(res, 401, { error: 'unauthorized' });
  const url = new URL(req.url, 'http://localhost');
  const match = /^\/churches\/([1-9][0-9]{0,9})(?:\/(connect|status|qr|send|disconnect))?$/.exec(url.pathname);
  if (!match) return json(res, 404, { error: 'not_found' });
  const [, id, action] = match;
  try {
    if (req.method === 'GET' && action === 'status') return json(res, 200, state(id));
    if (req.method === 'GET' && action === 'qr') {
      const qr = connections.get(id)?.qr;
      if (!qr) return json(res, 404, { error: 'qr_not_ready' });
      const image = await QRCode.toDataURL(qr, { errorCorrectionLevel: 'M', margin: 2, width: 320 });
      return json(res, 200, { image });
    }
    if (req.method === 'POST' && action === 'connect') return json(res, 202, await connect(id));
    if (req.method === 'POST' && action === 'disconnect') {
      const c = connections.get(id);
      connections.delete(id);
      if (c?.socket) c.socket.end(new Error('manual_disconnect'));
      return json(res, 200, { status: 'disconnected' });
    }
    if (req.method === 'POST' && action === 'send') {
      const c = connections.get(id);
      if (!c || c.status !== 'connected') return json(res, 409, { error: 'not_connected' });
      const body = await readJson(req);
      if (typeof body.text !== 'string' || body.text.length < 1 || body.text.length > 5000) return json(res, 422, { error: 'invalid_text' });
      if (typeof body.to !== 'string' || !/^(?:[1-9][0-9]{5,15}@s\.whatsapp\.net|[0-9-]+@g\.us)$/.test(body.to)) return json(res, 422, { error: 'invalid_recipient' });
      const result = await c.socket.sendMessage(body.to, { text: body.text });
      return json(res, 200, { status: 'sent_to_provider', message_id: result?.key?.id || null });
    }
    return json(res, 405, { error: 'method_not_allowed' });
  } catch (e) {
    console.error('worker request failed:', e?.message);
    return json(res, e.message === 'body_too_large' ? 413 : 500, { error: 'request_failed' });
  }
});
server.listen(port, host, () => console.log('Church Center WhatsApp worker listening on ' + host + ':' + port));
function shutdown(signal) {
  shuttingDown.value = true;
  console.log('Received ' + signal + ', stopping worker');
  server.close(() => process.exit(0));
  for (const c of connections.values()) c.socket?.end(new Error('shutdown'));
  setTimeout(() => process.exit(1), 10000).unref();
}
process.on('SIGINT', () => shutdown('SIGINT'));
process.on('SIGTERM', () => shutdown('SIGTERM'));
