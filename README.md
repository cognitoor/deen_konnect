# DeenKonnect — landing page

Pre-launch landing page for DeenKonnect, a platform connecting Muslims with verified
scholars, imams and teachers.

Built with plain PHP, HTML5, CSS3 and vanilla JavaScript. Email signups are stored in
Supabase. No Bootstrap, no jQuery, no CSS framework, no build step.

---

## Requirements

- PHP 8.1 or newer (uses `str_starts_with`, `str_contains`, typed returns)
- A Supabase project (free tier is enough)
- A web server — Apache, Nginx, or PHP's built-in server for local work

---

## Quick start

```bash
git clone <your-repo> deenkonnect
cd deenkonnect
cp .env.example .env      # then fill in your Supabase values
php -S localhost:8000
```

Open <http://localhost:8000>.

---

## Supabase setup

**1. Create the project**

Go to [supabase.com](https://supabase.com) → **New project**. Pick a region close to your
audience and save the database password somewhere safe.

**2. Create the table**

Open **SQL Editor → New query**, paste the contents of [`sql/subscriptions.sql`](sql/subscriptions.sql),
and click **Run**. This creates:

| Column       | Type                  | Notes                                     |
| ------------ | --------------------- | ----------------------------------------- |
| `id`         | `uuid`                | Primary key, auto-generated               |
| `email`      | `text`                | Unique (case-insensitive), format-checked |
| `created_at` | `timestamptz`         | Defaults to `now()`                       |
| `status`     | `subscription_status` | Defaults to `pending`                     |
| `ip_address` | `inet`                | Captured server-side by PHP               |
| `user_agent` | `text`                | Captured client-side                      |
| `source`     | `text`                | Defaults to `landing_page`                |

It also enables row level security with a single policy: anonymous visitors may
**insert** and nothing else. They cannot read, edit or delete the list.

**3. Get your keys**

**Project Settings → API** (or **Data API**):

- **Project URL** → `SUPABASE_URL`
- **anon / public key** → `SUPABASE_ANON_KEY`

**4. Put them in the environment**

```env
SUPABASE_URL=https://abcdefgh.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOi...
```

Locally, `.env` is enough — `config/config.php` reads it. In production, set real
environment variables instead (`SetEnv` in Apache, `fastcgi_param` in Nginx, or your
host's dashboard). Real environment variables always win over `.env`.

**5. Test it**

Submit an email on the page, then check **Table Editor → subscriptions**. Submit the
same address again — you should see "This address is already on the list."

### Is the anon key safe in the page?

Yes, and that's what it's designed for. It identifies your project; it doesn't grant
access. Row level security is what grants access, and here it grants exactly one thing:
inserting a pending signup. It's still read from the environment so you can rotate keys
per deployment without editing code.

Never put the `service_role` key anywhere near the front end. It bypasses RLS entirely.

---

## Configuration

All settings live in `.env` (see `.env.example`):

| Variable            | Purpose                                            |
| ------------------- | -------------------------------------------------- |
| `SUPABASE_URL`      | Your project URL                                    |
| `SUPABASE_ANON_KEY` | Public anon key                                     |
| `SUPABASE_TABLE`    | Table name (default `subscriptions`)                |
| `SITE_URL`          | Canonical URL, used for SEO tags                    |
| `CONTACT_EMAIL`     | Shown in the footer and schema.org data             |
| `LAUNCH_DATE`       | ISO 8601 UTC. Blank or past → the countdown hides   |

---

## Structure

```
project/
├── index.php               Page markup, SEO tags, config hand-off
├── assets/
│   ├── css/style.css       All styling, tokens first
│   ├── js/app.js           Reveals, countdown, form handling
│   ├── js/supabase.js      REST client — the only thing that talks to Supabase
│   ├── images/             Logo, favicon, OG image
│   └── icons/sprite.svg    One sprite for every icon
├── config/config.php       Env loading, escaping, client IP
├── sql/subscriptions.sql   Table, indexes, trigger, RLS policy
├── .env.example
└── README.md
```

---

## Design notes

Follows the brief's palette and type exactly.

| Token         | Value     |
| ------------- | --------- |
| Deep emerald  | `#0F6D4A` |
| Dark navy     | `#143642` |
| Gold          | `#D4AF37` |
| Off white     | `#FAFAF7` |
| Light gray    | `#F3F4F6` |
| Text          | `#333333` |
| Success       | `#28A745` |
| Error         | `#DC3545` |

Headings are **Cairo**, body is **Inter**. The signature element is a hairline eight-point
*khatim* star in the hero — geometry rather than ornament, cropped by the viewport edge so
it reads as architecture, not decoration. The gold appears only three times: the star, the
rule under "Islamic scholars", and the countdown labels. Everything else stays quiet.

---

## Accessibility

- Semantic landmarks, one `h1`, ordered headings
- Skip link, visible gold focus ring on every interactive element
- Labels on all inputs; status messages announced via `role="status"`
- The countdown gives screen readers one calm sentence instead of a ticking timer
- `prefers-reduced-motion` disables animation and reveals

## Performance

- No frameworks or runtime dependencies — CSS and two small JS modules
- One sprite for all icons; SVG favicon
- Fonts preconnected and loaded with `display=swap`
- Scripts are modules, so they never block paint

For the best score, serve over HTTP/2 with gzip or brotli and a long `Cache-Control` on
`/assets`.

## Security

- Email validated in the browser, in Postgres (`check` constraint), and pinned by RLS
- All PHP output escaped through `dk_e()`
- Honeypot field plus a per-session guard against double submission
- `X-Content-Type-Options`, `X-Frame-Options` and `Referrer-Policy` headers sent
- No secrets in the repository — `.env` is gitignored

---

## Still to do before launch

- Add `assets/images/og-image.png` (1200×630) and `apple-touch-icon.png`
- Write `privacy.php` and `terms.php` — the footer already links to them
- Point the social links at the real accounts (they're also in the JSON-LD in `index.php`)
- Publish `sitemap.xml`
