# WALE — WhatsApp AI Lead Engine

WALE is a self-hosted, multi-tenant web application for capturing WhatsApp
leads, managing conversations, sending AI-assisted replies, running reply-driven
funnels, and qualifying leads. It is built with CodeIgniter 4, PHP 8.2+, and
MySQL/MariaDB.

This repository contains a licensed CodeCanyon application and project-specific
configuration/documentation. Keep the original license terms and required
attribution with any deployment or academic submission. Do not represent
third-party source code as wholly original work.

Useful project documents:

- [`INSTALL.md`](INSTALL.md) — complete installation and deployment guide.
- [`public/user-guide.html`](public/user-guide.html) — end-user guide.
- [`sql/wale-schema.sql`](sql/wale-schema.sql) — database schema and starter data.

---

## Feature highlights

- Multi-tenant: each business connects its own WhatsApp number, OpenAI key and SMTP.
- WhatsApp Cloud API integration (inbound + outbound, 24-hour window aware).
- AI auto-replies (OpenAI) with per-tenant prompt, model and language.
- Conversation **Funnels** — reply-driven flows with branching, plus an AI
  funnel generator and a ready-made sample.
- **Message Templates** — sync approved templates from Meta, send to a lead,
  re-engage closed chats, or personalised broadcasts.
- Deterministic lead **scoring** + AI status qualification.
- Automations, Notifications, Analytics.
- **Fully translatable UI** — every string lives in `app/Language/`; switch the
  whole app (panel, emails, public pages) to any language from Branding settings.
- Super-Admin platform tools: tenants, plans, a public sales page, PayPal
  checkout with auto-provisioning, orders, email/SMTP, and white-label branding.

## Requirements

- PHP **8.2+** with extensions: `intl`, `mbstring`, `json`, `mysqlnd`, `curl`, `openssl`
- MySQL 5.7+ / MariaDB 10.3+
- Apache with `mod_rewrite` (or Nginx equivalent)
- Composer is **not** required — `vendor/` is bundled.

See [`INSTALL.md`](INSTALL.md) for the full setup procedure.

## Run locally

1. Clone the repository and enter the project directory.
2. Copy the environment template if needed and configure the database:

   ```bash
   cp env .env
   ```

   Set `CI_ENVIRONMENT = development`, the MySQL/MariaDB connection values,
   `app.baseURL`, and a unique `JWT_SECRET` in `.env`.

3. Create the database and import [`sql/wale-schema.sql`](sql/wale-schema.sql),
   or run the CodeIgniter migrations and seeders described in `INSTALL.md`.
4. Start the development server:

   ```bash
   php spark serve
   ```

5. Open <http://localhost:8080> in a browser. The default login and the
   mandatory first-login password change are documented in `INSTALL.md`.

Composer is not required for the supplied package when `vendor/` is present.
For a clean development checkout without `vendor/`, run `composer install`.

## Production deployment

Point the web server document root at `public/` only. Configure HTTPS, set
`CI_ENVIRONMENT = production`, use a strong unique `JWT_SECRET`, make
`writable/` writable by the web server, and configure the WhatsApp webhook at:

```text
https://your-domain.example/webhook/whatsapp
```

Then configure WhatsApp Cloud API, AI, SMTP, branding, and optional PayPal
settings from the application. Never commit `.env`, API keys, access tokens,
database credentials, or generated files from `writable/`.

## Security checklist (read before going live)

1. Set a **unique** `JWT_SECRET` in `.env` (the app refuses to run with the default).
2. Keep `CI_ENVIRONMENT = production` in `.env`.
3. Point your web server document root at the **`public/`** folder only.
4. Log in with the default super-admin and **change the password immediately**
   (you'll be forced to on first login).
5. Delete the demo user if you don't need it.

## License and attribution

The included framework license is in [`LICENSE`](LICENSE). The application is
distributed under the applicable Regular/Extended CodeCanyon license purchased
for it; consult the original CodeCanyon package and keep its license and
attribution requirements. Third-party services such as Meta WhatsApp Cloud API
and OpenAI have separate terms and usage costs.
