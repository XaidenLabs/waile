# WALE — Installation Guide

This guide gets WALE running on a normal shared host (cPanel) or a VPS.
Estimated time: **15–20 minutes**.

---

## 1. Requirements

- PHP **8.2 or higher** with extensions: `intl`, `mbstring`, `json`, `mysqlnd`, `curl`, `openssl`
- A MySQL / MariaDB database
- Apache with `mod_rewrite` (most hosts have this) or Nginx
- HTTPS (required by WhatsApp for webhooks)

> `vendor/` (all PHP dependencies) is already included — you do **not** need Composer.

---

## 2. Upload the files

1. Unzip the package.
2. Upload the **contents** to your server.
   - **Best practice:** upload everything above the web root, then point your
     domain's document root at the **`public/`** folder.
   - **Shared hosting without docroot control:** upload everything into
     `public_html/` (or a subfolder). The included `public/.htaccess` handles
     clean URLs. Never expose the project root — only `public/` should be web-reachable.

---

## 3. Create the database

1. In cPanel → **MySQL Databases** (or your host panel), create a database and a user, and grant the user all privileges on it.
2. Note the **database name, username, and password**.

---

## 4. Configure `.env`

Open the `.env` file in the project root and set:

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://yourdomain.com/'      # your real URL, WITH trailing slash

# Database
database.default.hostname = localhost
database.default.database = your_db_name
database.default.username = your_db_user
database.default.password = your_db_password
database.default.DBDriver = MySQLi

# REQUIRED — unique random secret (encrypts stored tokens + signs API logins).
# Generate one:  php -r "echo bin2hex(random_bytes(32));"
JWT_SECRET = paste-a-long-random-unique-value-here
```

> ⚠️ **The app will not run until `JWT_SECRET` is changed from its placeholder.**
> This protects the WhatsApp/OpenAI tokens your customers store.

Optional (each tenant can also set these inside the app):

```ini
OPENAI_API_KEY = sk-...            # global fallback AI key
OPENAI_MODEL   = gpt-4o-mini
```

If your host doesn't expose CLI database settings via `.env`, you can instead
edit `app/Config/Database.php`.

---

## 5. Create the tables

Run migrations to build the schema, then seed the starter data:

```bash
php spark migrate
php spark db:seed AdminUserSeeder     # creates the super-admin login
php spark db:seed PlansSeeder         # creates default subscription plans
```

**No SSH/terminal?** You have two options:

- **Easiest — import the SQL file:** in phpMyAdmin, select your database →
  **Import** → choose **`sql/wale-schema.sql`** → Go. This creates every table,
  the starter plans, and the default admin in one step. If you use this, you do
  **not** need to run `php spark migrate` or the seeders — skip to step 6.
- Or, in cPanel use **Terminal**, or a one-off **Cron Job** running
  `php /home/USER/path/to/spark migrate`.

---

## 6. First login

1. Go to `https://yourdomain.com/` and log in:
   - **Email:** `admin@wale.dev`
   - **Password:** `Admin@1234`
2. You'll be **required to set a new password** immediately. Do it.
3. (Optional) Delete the demo user (`demo@wale.dev`) from **All Tenants** if unwanted.

---

## 7. Connect the services

Inside the app:

- **Settings → WhatsApp** — enter your Meta WhatsApp Cloud API credentials
  (Phone Number ID, Access Token, WABA ID, Verify Token). Set your webhook URL
  in Meta to: `https://yourdomain.com/webhook/whatsapp`
- **Settings → AI Config** — add your OpenAI API key and system prompt.
- **Super Admin → Email / SMTP** — configure the mail server for system emails.
- **Super Admin → Branding** — upload your logo, colors, app name, and choose the
  **Application Language** (see *Languages & translating* below).
- **Super Admin → Plans / Sales & Payments** — define plans and (optionally)
  enable the public pricing page + PayPal checkout.

The complete click-by-click Meta setup is documented in `public/user-guide.html`
(section **"Meta Setup (Step-by-Step)"**).

---

## 8. File permissions

Ensure the **`writable/`** folder (and its subfolders) is writable by the web
server (typically `755`, or `775` on some hosts).

---

## Languages & translating

Every user-facing string (admin panel, login/pricing pages, system emails, flash
messages, dropdowns) is fully translatable. The English strings live in
**`app/Language/en/`** — one PHP file per screen area (e.g. `Leads.php`,
`Settings.php`, `Messages.php`).

**To switch the app to another language:**

1. **Copy** the English folder to a new locale code, e.g. Spanish:
   ```
   app/Language/en/   →   app/Language/es/
   ```
2. **Translate the values** in each `app/Language/es/*.php` file. Change only the
   text on the right — keep the array keys and the `{0}`, `{1}` placeholders exactly
   as they are:
   ```php
   // app/Language/es/Auth.php
   'signIn'      => 'Iniciar sesión',
   'welcomeBack' => 'Bienvenido de nuevo, {0} 👋',
   ```
   Any key you don't translate automatically falls back to English, so a partial
   translation never breaks a page.
3. **Select it in the app:** go to **Super Admin → Branding → Application Language**
   and pick your language from the dropdown, then save. The new folder is detected
   automatically — no code or config changes needed.

Right-to-left languages (Arabic, Hebrew, Farsi, Urdu…) automatically get
`dir="rtl"` on the page.

> The dropdown only lists languages you've actually added a folder for, so English
> is the only option until you create your first translation.

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| Blank page / 500 error | Temporarily set `CI_ENVIRONMENT = development` in `.env` to see the error, fix it, then switch back to `production`. |
| "Setup required: JWT_SECRET" page | You haven't set a real `JWT_SECRET` in `.env` — set one (step 4). |
| Clean URLs 404 | Ensure `mod_rewrite` is enabled and the doc root points at `public/`. |
| WhatsApp messages not arriving | Check the webhook URL + verify token in Meta, that the `messages` field is **subscribed**, the site is HTTPS — and that your host isn't **blocking Facebook** (see below). |
| "Table doesn't exist" | Run `php spark migrate` (step 5). |

### Messages send fine, but never appear in the app (host firewall blocking Meta)

If outbound works but **incoming messages never reach WALE** — Meta's logs show the message as delivered, yet no conversation appears — your **hosting security layer is very likely blocking Facebook's webhook requests**.

Meta calls your webhook from Facebook IP ranges with the User-Agent **`facebookexternalua`**. Many shared hosts run **ModSecurity / imunify360 / a WAF** that blocks these, returning a `403`/`406` before the request ever reaches WALE.

**How to confirm:** in cPanel → **Metrics → Raw Access** (or your `access_log`), send a test message from your phone and look for a `POST` to `/webhook/whatsapp`. If you see it with a **403 / 406** status (often from a Facebook user-agent), the firewall is the cause. Your **ModSecurity log** will name the exact rule.

**How to fix (any one):**
1. Ask your host to **whitelist** the `facebookexternalua` user-agent and/or [Facebook's webhook IP ranges](https://developers.facebook.com/docs/graph-api/webhooks/getting-started#webhook-security) for the `/webhook/whatsapp` path.
2. **Disable the specific ModSecurity rule** that's blocking it: `SecRuleRemoveById <id>` (get the ID from the ModSecurity log).
3. Disable ModSecurity for the webhook path (if your host allows per-path/per-domain control in cPanel).

**Tip to isolate app vs. host:** POST a sample event to your webhook with `curl`. If that creates a conversation but real Meta messages don't, the app is fine and the block is your host's firewall:
```bash
curl -i -X POST "https://yourdomain.com/webhook/whatsapp" \
  -H "Content-Type: application/json" \
  -d '{"object":"whatsapp_business_account","entry":[{"id":"WABA_ID","changes":[{"value":{"messaging_product":"whatsapp","metadata":{"display_phone_number":"1555...","phone_number_id":"YOUR_PHONE_NUMBER_ID"},"contacts":[{"profile":{"name":"Curl Test"},"wa_id":"910000000000"}],"messages":[{"from":"910000000000","id":"wamid.TEST1","timestamp":"1700000000","text":{"body":"Hello"},"type":"text"}]},"field":"messages"}]}]}'
```
Replace `YOUR_PHONE_NUMBER_ID` with the value from **Settings → WhatsApp**. A "Curl Test" conversation should appear.

For everyday usage, open **`public/user-guide.html`**.
