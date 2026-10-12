# Changelog

All notable changes to WALE are documented here.
This project follows [Semantic Versioning](https://semver.org/).

---

## [1.1.0] — July 2026

### Added
- **Full multi-language support** — every user-facing string (panel, login/pricing
  pages, emails, flash messages, dropdowns) is translatable via `app/Language/`.
  Drop in a translated folder and pick it under **Branding → Application Language**;
  right-to-left languages get `dir="rtl"` automatically.
- **Message Templates** — sync approved WhatsApp templates from Meta, send to a
  single lead, re-engage a closed conversation, or run a **personalised broadcast**
  (map each template variable to a lead field like name/phone, or a fixed value).
- **AI Funnel Generator** — describe a funnel in plain words (and optionally
  upload a flowchart image); the AI drafts the whole funnel for review.
- **Load Sample Funnel** — one-click ready-made lead-qualification funnel.
- **Notifications centre** — full history page with type filters, plus the
  top-bar bell, for delivery failures and system alerts.
- **Deterministic lead scoring** — engagement-based score (0–100) that updates
  on every inbound message, independent of AI.
- **Forced password change** — seeded default accounts must set a new password
  on first login.
- **Setup guard** — the app refuses to run until a unique `JWT_SECRET` is set.
- **SQL import fallback** (`sql/wale-schema.sql`) for hosts without CLI access.
- Buyer **INSTALL.md** and a rewritten **README.md**.

### Changed
- Lead **status** is set by AI qualification; the numeric **score** is now owned
  by the deterministic scoring engine (no more conflicts / stuck-at-0 scores).
- Funnel/automation notes are attributed to the funnel/automation name, not the
  admin user.
- Ships with `CI_ENVIRONMENT = production` by default.

### Fixed
- Failed WhatsApp sends now surface everywhere: a notification, a reason in the
  conversation thread, and the bell — including asynchronous delivery failures
  reported by Meta (e.g. the 24-hour window).
- Funnel variables that weren't collected in a lead's path no longer render as
  raw `{placeholders}` in notes/messages.
- Lead scoring now runs for funnel-driven conversations, not only AI replies.

---

## [1.0.0] — June 2026

### Added
- Initial release: multi-tenant WhatsApp AI lead engine.
- WhatsApp Cloud API integration (inbound + outbound), AI auto-replies (OpenAI),
  conversation funnels, automations, lead management, analytics.
- Super-Admin platform: tenants, plans, public sales page, PayPal checkout with
  auto-provisioning, orders, email/SMTP settings, and white-label branding.
