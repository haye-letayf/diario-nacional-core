# CLAUDE.md

This file provides guidance to Claude Code when working in this repository.

## What this is

Diario Nacional is a Mexican platform where lawyers and notaries publish legal edictos (a required public-notice step in certain judicial processes). This plugin (`diario-nacional-core`) is the **rebuild**, from scratch, of a two-year-old legacy PHP system (no framework, ~9,000 lines of procedural code) — the goal is a solid, well-structured WordPress plugin, not a line-by-line port. The companion repo `diario-nacional-theme` owns presentation only; this plugin owns everything that touches money, legal compliance, or user data.

**The legacy system is the source of truth for *behavior*, not for *code*.** A full technical audit of it — architecture, data model, every integration contract, every security finding — was written up before this rebuild started. Read it before touching anything domain-specific: **https://claude.ai/artifact/Fmg4eEzUNc4VsTwpaVVzs8** ("Expediente Diario Nacional"). It documents things that are easy to get subtly wrong if re-derived from scratch, most importantly the edicto republication logic and the 30-day public search gate — both have real legal weight (edictos require proof of publication in a "medio de comunicación masiva," and the republication dates are a legal requirement, not a marketing feature).

The legacy system stays live and untouched while this is built — there is exactly one real client today, so migration risk tolerance is high, but nothing gets cut over until this plugin is a verified equivalent.

## Business domain, in brief

- **Edictos**: a notary account buys credit packages (~$750 MXN/edicto). Publishing one consumes a credit and schedules up to 4 automatic future "republication" dates (a legal requirement) — same content, new date, no new record. Public search only shows the last 30 days unless the viewer has an active annual license, in which case the date filter is dropped entirely.
- **Noticias**: extracts (title/excerpt/image/link only, never full content) from other outlets' RSS feeds — this is what legally qualifies the platform as a "medio de comunicación masiva" in the first place. The link always sends readers off-site.
- **Facturación**: every purchase can be invoiced via Digifact/Sicofi (`cfd.sicofi.com.mx`). **This integration is ported as close to 1:1 as possible** — the client (Jorge) has explicitly asked not to renegotiate anything with Digifact. The full request/response contract (CFDI 4.0 payload shape, auth, PDF retrieval) is documented in the audit artifact linked above.
- **Evidencia**: every publish and every republication triggers an email with a PDF (that day's front page + the edicto) as legal proof of publication. Users can also download the PDF for any specific past date.

## Architecture decisions for this rebuild

- **Plugin (this repo) = business logic. Theme (`diario-nacional-theme`) = presentation only, hand-coded, no page builder.** Same split as OpenREAL Cloud (a sibling Once24 project) — the plugin must survive a full theme redesign untouched.
- **Transactional data lives in custom tables via `$wpdb`, not post/postmeta.** Edictos, credits, purchases, and invoices are relational data with real query patterns (the 5-date filter, credit balances) — forcing them into WordPress's content model would be the worst of both worlds. This is the one point non-negotiable from the audit's recommendation.
- **News extracts are the one place WordPress content modeling actually fits** — CPT + ACF, nothing custom.
- **Stripe, direct, with a real webhook.** The legacy system has no Stripe webhook at all — credit fulfillment depends entirely on the customer's browser returning to a success URL, which means a closed tab can silently swallow a paid credit. The rebuild makes `checkout.session.completed` the single source of truth for crediting an account; any return-URL page is cosmetic only.
- **Zero interpolated SQL, ever.** The legacy codebase has confirmed, exploitable SQL injection because none of its ~150 raw queries use prepared statements. Every query here goes through `$wpdb->prepare()` — no exceptions, no "just this once."
- **Secrets never touch the repo.** Stripe keys, Digifact credentials, DB access — all via `wp-config.php` constants or environment variables, never hardcoded in a tracked file. (The legacy system had a live Stripe secret key, a live DB password, and a Google service-account private key all committed in plain text — see the audit for the full list. Don't repeat that.)
- **Password migration, no forced reset**: the legacy password hashes are standard PHP `password_hash()` (bcrypt). A custom `wp_authenticate` filter can validate against the old hash on first login and transparently re-hash it — the one real user never needs to reset anything.
- **Forms**: Fluent Forms Pro for the edicto-submission intake (many fields — juzgado, expediente, involucrados, estado, municipio, tipo de aviso) so Jorge can keep tweaking the form himself without a developer.
- **Outgoing email**: Jorge's existing AWS SES account, not the legacy system's Gmail Workspace SMTP credentials.
- **Not carrying forward**: the legacy Google-Sheets bulk edicto importer (`carga_juzgados.php`) — it was a content-padding experiment that never got traction and nobody uses today. Revisit only if Jorge wants to redesign that idea deliberately later.

## Open questions not yet resolved (don't assume — ask Jorge)

- Where the legacy daily cron/trigger lives for the "your edicto just republished" notification email — not found anywhere in the legacy code, likely an external cPanel cron hitting a URL that wasn't in the source zip.
- Exactly where legacy Stripe credit fulfillment writes to `edictos_shop` — wasn't found in `stripeok.php` despite looking; may not matter once the webhook replaces that whole path, but worth understanding before assuming the old behavior is fully captured.

## Environments

Local (LocalWP) → staging (`dev.diarionacional.com.mx/a`, deployed via cPanel Git Version Control) → Jorge's existing cPanel production at `diarionacional.com.mx` (cutover process TBD, deliberately deferred until staging is a verified equivalent of the legacy system). Same pattern as OpenREAL. Nothing gets pushed to any shared branch or deployed without Jorge's explicit go-ahead — he drives commits/pushes/deploys himself; treat that as the default even when it would be faster to do it directly.

**cPanel Git Version Control mechanics** (this tripped us up once — document it so it doesn't again): the "Repository Path" set in GVC (`/home/edictosyavisosno/repositories/diario-nacional-core`) is just where cPanel keeps its working copy — it is *not* where WordPress reads the plugin from. `.cpanel.yml` in this repo's root defines the actual deployment target via a `deployment.tasks` copy step; without it, GVC's "Deploy HEAD Commit" button stays disabled. Current deploy target:

```
/home/edictosyavisosno/public_html/dev.diarionacional.com.mx/a/wp-content/plugins/diario-nacional-core/
```

Deploy flow after every push: GVC → **Update from Remote** → **Deploy HEAD Commit**. When production cutover happens, `.cpanel.yml` needs a second deploy target added (or swapped) for `diarionacional.com.mx` — same pattern OpenREAL uses for its `staging` vs `main` branches having different `.cpanel.yml` deploy lists, don't let one overwrite the other's target when that day comes.

## Status

Fase 0 (this commit): repo scaffolding only. See the audit artifact for the full phase plan (0 through 7, ~45-day target).
