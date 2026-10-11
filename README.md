# Church Center

Multi-tenant church management SaaS and WhatsApp automation platform.

> Status: planning / implementation pending. This repository is not yet a working application.

## Architecture (planned)
- `apps/web`: Laravel backend, React + Inertia dashboard, Tailwind CSS UI.
- `apps/whatsapp-worker`: Node.js WhatsApp worker with isolated church sessions.
- `packages`: shared contracts and formatting utilities.
- `infrastructure`: deployment scripts and service configuration.
- `docs`: architecture, operations and user guides.

## Roles
- **Platform Admin**: manages churches, pricing, subscriptions, payments, global service controls, maintenance, support, and monitoring.
- **Fulltime**: manages their church, contacts, staff permissions, WhatsApp bot, and automations.
- Congregation members do not need an account; they interact through WhatsApp or optional public forms.

## Planned modules
- Google OAuth and Google Contacts import, church-scoped staff permissions.
- WhatsApp connection, custom bot menus, Auto Reply and shared FAQ rule engine.
- Scheduled daily devotionals (morning/noon/evening), events, worship reminders, birthdays.
- One-time and recurring broadcasts, consent and delivery logs.
- Licenses (1/3/6/12 months), manual bank transfer approval, optional GoPay Merchant QRIS integration.
- Support tickets, configuration backup/restore, monitoring, global switches, maintenance and audit logs.

## Engineering rules
- Organize by responsibility; avoid giant controllers, services and JavaScript files.
- Reuse stable libraries and shared components; write readable code with tests.
- Every tenant-owned record and WhatsApp session must be isolated by church.
- Never commit `.env`, credentials, payment secrets, OTP, personal contact exports or WhatsApp session data.
- Payment activation must be idempotent and based on verified payment status.
- Respect recipient consent, rate limits and WhatsApp platform restrictions.

## Next steps
Bootstrap the applications, implement authentication and tenant isolation, then add dashboard modules, WhatsApp worker, payments and end-to-end tests.
