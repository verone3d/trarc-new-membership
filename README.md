# TRARC Online Membership Application

A dependency-light form that replaces the paper/PDF TRARC membership
application. On submit, it emails a clean, formatted copy of the applicant's
answers to the club board so the board can review and accept/reject
membership offline. It does **not** implement any approval workflow, login
system, or database — this app's only job is capture + notify.

Fields, order, and required/optional status are transcribed directly from
`TRARC_Member_Application_2024-02-27.pdf` so officers can compare submissions
to the paper form.

There are two parallel deployment targets sharing the same fields/validation/
email content, for use depending on which hosting is available:

- **IONOS (PHP + SMTP)** — `index.php`, `submit.php`, `config.php`, `lib/PHPMailer/`.
- **Cloudflare Workers (GitHub-connected, no PHP)** — a Worker with static
  assets: `cloudflare/index.html` is served as a static file, and
  `src/worker.js` handles `POST /submit`, sending mail via the Resend HTTP
  API instead of SMTP.

Only one needs to be live at a time; keeping both in the repo means switching
hosts later is a redeploy, not a rewrite.

## File structure

```
index.php               IONOS: form page (fresh, no errors)
submit.php               IONOS: handles POST: validates, sends email, shows result
config.php                IONOS: SMTP + board recipient settings (fill in real values)
config.sample.php        IONOS: checked-in template of config.php with placeholders
style.css                 Shared styling, no external dependencies (copied into cloudflare/)
includes/functions.php   IONOS: small shared helper functions
includes/form.php         IONOS: the form markup, shared by index.php and submit.php
lib/PHPMailer/            IONOS: vendored PHPMailer source (PHPMailer.php, SMTP.php, Exception.php)
.htaccess                 IONOS: denies direct web access to config.php / config.sample.php
includes/.htaccess        IONOS: denies direct web access to includes/
lib/.htaccess             IONOS: denies direct web access to lib/

cloudflare/index.html    Cloudflare Worker: static form page (served via the assets binding)
cloudflare/style.css     Cloudflare Worker: copy of style.css
src/worker.js             Cloudflare Worker: fetch handler — routes POST /submit itself,
                           everything else falls through to the assets binding
src/shared.js             Cloudflare Worker: shared HTML/email-body rendering
wrangler.toml             Cloudflare Worker config (entry point, assets directory, compat date)
.dev.vars.example        Template for local `wrangler dev` secrets (copy to .dev.vars)
```

## Deployment on Cloudflare Workers (GitHub-connected)

Use this path if you don't currently have IONOS hosting access but do have
GitHub + Cloudflare.

1. Push this repo to GitHub (`git init`, commit, `gh repo create` or create
   the repo on github.com, then push). `config.php` and `.dev.vars` are
   git-ignored so no real secrets end up in the repo.
2. In the Cloudflare dashboard: **Compute (Workers) → Create → Connect to
   Git**, pick the GitHub repo. Cloudflare reads `wrangler.toml` for the
   entry point (`src/worker.js`) and static assets directory (`cloudflare/`)
   automatically — no separate build command needed, since `wrangler.toml`
   already sets `main` and `[assets]`.
   - If the dashboard shows a "Deploy command" field, it should be
     `npx wrangler deploy`. (An earlier attempt at this used the older,
     separate "Pages" product with a `pages_build_output_dir` setting —
     that's been superseded by Workers' built-in static assets support,
     which is what `wrangler.toml` is now configured for.)
3. Set these as **environment variables/secrets** on the Worker project
   (Settings → Variables and Secrets, for both Production and Preview):
   - `RESEND_API_KEY` — API key from [resend.com](https://resend.com) (free
     tier: ~3,000 emails/month, more than enough for a club form)
   - `FROM_EMAIL` — sending address on a domain verified in Resend
   - `FROM_NAME` — optional, defaults to "TRARC Membership Application"
   - `BOARD_RECIPIENTS` — comma-separated board email address(es)
   - `SEND_APPLICANT_CONFIRMATION` — `true` to also email applicants a short
     confirmation
4. Deploy. Cloudflare rebuilds automatically on every push to the connected
   branch.
5. In Resend, verify the sending domain (DNS records Resend gives you) before
   `FROM_EMAIL` will actually deliver — unverified domains get rejected or
   land in spam.
6. Test locally first if you want: `npx wrangler dev` (copy
   `.dev.vars.example` to `.dev.vars` and fill in real values — it's
   git-ignored).
7. Test the full flow end-to-end after deploy, same checklist as the IONOS
   steps below (valid submit, missing required fields, under-18 branch,
   confirm the board actually receives the email).

### Migrating from Cloudflare back to IONOS later

No code changes needed — `index.php`/`submit.php`/`config.php`/`lib/PHPMailer/`
were never touched by the Cloudflare setup. When IONOS access is available:

1. Follow the "Deployment on IONOS shared hosting" steps below as normal.
2. You don't need to upload `cloudflare/`, `src/`, or `wrangler.toml` to
   IONOS — they're inert there, but there's no harm leaving them out.
3. Point the domain/DNS at IONOS instead of Cloudflare (or keep both live at
   different subdomains/paths if useful during a transition).

## Deployment on IONOS shared hosting

1. Upload all files (including the `lib/` and `includes/` folders and their
   `.htaccess` files) via IONOS File Manager or FTP into `public_html/`
   (or a subfolder, e.g. `public_html/join/`, if the club wants it at a
   sub-path).
2. In the IONOS hosting control panel, confirm/set the PHP version for the
   domain to 8.1 or newer.
3. **Fill in real values in `config.php`** before this will work:
   - `smtp_host` / `smtp_port` / `smtp_secure` — the SMTP settings for the
     mailbox you're sending from (confirm the exact IONOS SMTP host for that
     mailbox in the IONOS control panel).
   - `smtp_username` / `smtp_password` — credentials for that mailbox.
   - `from_email` / `from_name` — the sending address/display name.
   - `board_recipients` — one or more real board email addresses.
   - `send_applicant_confirmation` — `true` to also email applicants a short
     confirmation when they provide an email address.

   `config.php` should never be committed to a public repository with real
   secrets in it — keep it out of version control or in a private repo only.
4. Confirm the SMTP mailbox is an actual IONOS-hosted mailbox (or another
   provider's SMTP the club has credentials for). IONOS shared hosting's
   outbound `mail()` function is unreliable/frequently flagged as spam,
   which is why this app uses SMTP via PHPMailer instead.
5. If the hosting cert supports HTTPS (it should, via IONOS's free
   Let's Encrypt cert), add a redirect rule so the form is only served over
   HTTPS. PHP itself can't enforce this — add an HTTP→HTTPS rule in
   `.htaccess` or the IONOS control panel.
6. Test the full flow end-to-end after upload:
   - Submit with all required fields filled in correctly.
   - Submit with missing required fields — confirm inline errors appear and
     previously entered values are preserved.
   - Submit indicating the applicant is under 18 — confirm the
     parent/guardian signature field is required.
   - Confirm the board actually receives the email (check the spam folder on
     the first test).

## Security notes

- All required fields are validated server-side in `submit.php` — client-side
  JS (show/hide toggles) is a convenience only, not the real gate.
- All user input is HTML-escaped before being echoed back into the form.
- A hidden honeypot field (`website`) silently discards likely-bot
  submissions without sending mail.
- A simple session-based 30-second rate limit deters accidental double
  submissions.
- Fields that could end up in email headers (e.g. the applicant's email used
  as Reply-To) are stripped of CR/LF characters to prevent header injection.
- `config.php`, `includes/`, and `lib/` are blocked from direct web access via
  `.htaccess`.

## Future enhancements (not built — out of scope for this app)

- Any admin/approval workflow (Approved/Disapproved, membership type
  granted, dues collected, President/Secretary/Treasurer sign-off) — the
  board still handles that manually after reading the email, matching the
  "FOR ADMINISTRATIVE USE" section of the original paper form.
- Storing submissions in a database or building a searchable log.
- Generating/attaching a filled copy of the actual PDF to the email (this
  app sends a formatted email summary instead).
- Real digital signatures (this uses a typed-name attestation, not a
  signature pad).
- CAPTCHA/reCAPTCHA — the honeypot + rate limit are enough for a low-traffic
  club form; add this if spam becomes a problem.
