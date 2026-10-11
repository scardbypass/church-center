# WhatsApp worker — setup and limitations

The worker now uses Baileys to create separate sessions by numeric church ID. This is an **unofficial** WhatsApp client and can disconnect or result in account restrictions. Use only with consent and an account owned by the church. Do not advertise guaranteed delivery.

## Install on VPS

```bash
cd church-center/apps/whatsapp-worker
npm install
node --check chiwa.js
openssl rand -hex 32
```

Store the generated value as `WORKER_API_TOKEN` in the PM2 environment (never commit secrets). Set `WORKER_SESSION_DIR` to a persistent private directory, with file permissions restricted to the worker account. Set `WORKER_HOST=127.0.0.1`. Restart with `pm2 restart church-center-bot --update-env` or start using the repository's PM2 ecosystem file after setting its environment.

The worker intentionally refuses to start without a strong token and refuses public listening addresses. `GET /health` is a local liveness endpoint. All other endpoints require `Authorization: Bearer <WORKER_API_TOKEN>`.

## Internal API

- `POST /churches/1/connect` starts session and returns its state.
- `GET /churches/1/status` returns `connecting`, `qr_ready`, `connected`, or `disconnected`; the QR string can be rendered in a trusted admin interface. Treat it as a login credential.
- `POST /churches/1/send` with JSON `{"to":"628123456789@s.whatsapp.net","text":"Halo"}` sends text when connected. Group IDs end in `@g.us`.
- `POST /churches/1/disconnect` closes the in-memory socket; it does **not** erase saved credentials.

Example (run locally on VPS, replace placeholders):

```bash
curl -sS -H "Authorization: Bearer $WORKER_API_TOKEN" http://127.0.0.1:31181/churches/1/status
```

**Critical production gaps:** This worker is not yet connected to Laravel's tenant/role/consent/license enforcement; it must not be exposed to the internet or untrusted users. QR presentation in the web dashboard, scheduled queue delivery, opt-out, rate limits, group targeting authorization, retry guarantees, media, encrypted session backups, webhook verification, and integration tests are not complete. The worker's basic `menu` response is hard-coded, not the configurable church FAQ. PM2 and provider connectivity have not been tested on the user's VPS. Baileys dependency versions need lockfile generation and security review during deployment.

Do not enable scheduled or bulk sending until tenant-scoped authorization and consent enforcement are integrated.
