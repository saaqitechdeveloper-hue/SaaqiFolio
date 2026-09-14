# Folivo — Designer Portfolio Platform (PHP + MySQL)

A full server-side rewrite of the original HTML/CSS/JS designer-portfolio app.
Same visual design, same features — now backed by real accounts and a real
MySQL database instead of browser-only state.

## Features Included

- **Signup / Login / Logout** — passwords stored with bcrypt (`password_hash`)
- **Forgot Password** — enter your email, set a new password directly
  (matches the original demo's flow — see "Security Notes" below)
- **Profile**
  - View mode (exactly like the original "my profile" screen) + Edit mode
  - Name, profession, phone, address, bio, education, experience
  - Software/skills checklist (Photoshop, Figma, Illustrator, etc.)
  - Avatar upload
  - Banner upload, with **position (drag up/down) and zoom sliders** —
    saved to the database so it's remembered next time
  - "Reset banner" to fall back to the auto-collage of your latest work
  - CV/Resume upload (PDF/DOC/DOCX) with download link, and remove option
  - **Download portfolio as PDF** (see "Trial Limits" below)
- **Portfolio**
  - Multi-image upload — the 20-image free limit is enforced **mid-batch**
    too, so a bulk upload stops exactly at 20 and shows an Upgrade prompt
    immediately, rather than only blocking the *next* upload attempt
  - Category tabs (Logo, Banner, UI/UX, Color Separation, Flyer, Poster,
    Social Media, Other) with per-category counts
  - Change a single image's category anytime (dropdown on each card)
  - **Bulk select** mode — select several images and move them all to a
    category in one click
  - **Delete images** — the delete (✕) button sits directly on each
    image thumbnail
- **Client / Public View** (`p.php?slug=your-name`)
  - A shareable, read-only link showing your banner, avatar, bio,
    education, experience, skills, CV download, and full categorized
    portfolio grid — exactly what a client sees
  - If you're logged in and viewing your own link, a small banner reminds
    you it's a live preview
- **Upgrade to Pro** (`dashboard/upgrade.php`)
  - Real payment via **JazzCash** and **Easypaisa** (both also accept
    Pakistani bank debit/credit cards directly on their own hosted
    payment page — no separate "Card" integration needed)
  - On a verified successful payment, the account is automatically
    flipped to Pro: unlimited images, unlimited PDF downloads
- **Admin Panel** (`/admin/`) — see "Admin Panel" section below

## Trial Limits

| | Trial | Pro |
|---|---|---|
| Portfolio images | 20 | Unlimited |
| PDF downloads | 3 | Unlimited |
| Price | Free | Rs. <configurable> / month |

Both limits live in `config/db.php`:
```php
define('FREE_IMAGE_LIMIT', 20);
define('FREE_PDF_LIMIT', 3);
define('PRO_PRICE', 999.00);
```

## Admin Panel

A completely separate login for you (the site owner) — not visible to
regular users, and uses its own `admins` table and its own session, so it
never mixes with a designer's account.

**Setup (one-time):**
1. Import/update `database/schema.sql` (adds the `admins` table).
2. Visit `http://localhost/folivo/admin_setup.php` in your browser and
   create your admin username/password.
3. **Delete `admin_setup.php` from the server** right after — same
   security reasoning as elsewhere in this app.
4. Log in at `http://localhost/folivo/admin/login.php`.

**What you can see and do:**
- **Dashboard** (`admin/dashboard.php`) — total signups, Pro vs Trial
  breakdown, new signups this month, and **revenue broken down by Today
  / This Week / This Month / This Year / All Time** (only counts
  successfully completed payments), plus a quick view of recent signups
  and recent payments.
- **Users** (`admin/users.php`) — every account, searchable by name/email,
  filterable by plan. Shows each user's image count, PDF downloads used,
  and join date.
- **Edit User** (`admin/edit_user.php`) — click any user to:
  - Edit their name, email, profession, phone, address, bio
  - **Grant or remove Pro** manually (e.g. for a promo, a manual bank
    transfer, or a refund) — independent of the JazzCash/Easypaisa flow
  - Reset their PDF download counter
  - Set a new password for them (support/lockout scenario)
  - See their full payment history
  - Delete their account entirely (with confirmation)
- **Payments** (`admin/payments.php`) — every transaction attempt across
  all users (Completed/Pending/Failed), filterable by status — this is
  your "how much have I actually earned" ledger, sourced from the same
  `payments` table the JazzCash/Easypaisa callbacks write to.

Deleting a user from the admin panel removes their portfolio images, CV,
avatar/banner files from disk, and their database rows (portfolio,
payments) via cascading foreign keys — same safety confirmation step as
deleting from a user's own dashboard.

## PDF Design

The "Download PDF" button now opens with a colored cover banner (using
Folivo's own brand purple) showing a circular avatar photo, name, role,
and contact line — matching the app's own visual identity rather than a
plain white page.

Images per row also differ by category, since that's what actually looks
right for each type of work:
- **Logo** — 4 per row (logos are small/square, more fit comfortably)
- **UI/UX** — 3 per row
- **Banner** — 2 per row (banners are wide, so fewer per row keeps them legible)
- All other categories — 3 per row (default)

You can change these in `dashboard/download_pdf.php` (`$colsByCategory` array).


## What's Different From the Original (and Why)

The original was a single-file browser demo (`localStorage`, no server).
Porting it to real PHP + MySQL required a few honest simplifications:

1. **"AI" image categorization** — the original didn't call a real AI
   vision API either; on upload, this version applies the same
   lightweight *filename-keyword* guess (e.g. a file named `logo-v2.png`
   defaults to "Logo"), and you can change the category anytime. Real
   image-recognition categorization would need a paid vision API
   (Google Vision, AWS Rekognition, etc.) wired in separately.
2. **Payment gateways are wired up but need YOUR merchant credentials.**
   I don't have (and can't get) your JazzCash/Easypaisa merchant account
   details, so I've built the full request/response flow — hashing,
   redirect, callback verification, auto-upgrade on success — using
   placeholder credentials you must replace. See "Payment Gateway Setup"
   below. **I could not test a real transaction** since that requires a
   live merchant account; please run a small real test payment yourself
   before relying on this in production, and let me know if a field name
   needs adjusting (gateway APIs occasionally change their exact field
   names between merchant agreement types).
3. **PDF export uses a small built-in PDF writer, not a full library.**
   This was a deliberate choice so nothing needs installing — but it
   means: only basic Latin/English text renders correctly (no Arabic-script
   Urdu font support), and layout is simpler than a full HTML-to-PDF
   engine would produce (no custom fonts, no complex CSS). If you later
   want a richer-looking PDF (custom fonts, multi-language text), that
   would need a real library like dompdf — happy to wire that in if you
   get Composer working, or if your host's `exec()` is enabled we could
   use a headless-Chrome/wkhtmltopdf approach instead.

Everything else (auth, profile, banner position/zoom, portfolio
categorization, bulk actions, public client view) is fully functional
against the real database.

## Payment Gateway Setup (JazzCash & Easypaisa)

Both gateways work the same way: the app builds a signed form, auto-submits
it to the gateway's hosted payment page (where the customer enters their
JazzCash/Easypaisa account **or their bank card**), and the gateway
redirects back to a callback page here that verifies the payment and
upgrades the account.

### JazzCash
1. Apply for a JazzCash merchant account (business documents required).
2. From your merchant dashboard, get your **Merchant ID**, **Password**,
   and **Integrity Salt**.
3. Paste them into `config/db.php`:
   ```php
   define('JAZZCASH_MERCHANT_ID', '...');
   define('JAZZCASH_PASSWORD', '...');
   define('JAZZCASH_INTEGRITY_SALT', '...');
   ```
4. Test on the sandbox URL first (already set as the default in
   `JAZZCASH_API_URL`), then switch to the live URL JazzCash gives you
   once they approve you to go live.

### Easypaisa
1. Apply for an Easypaisa merchant/store account.
2. Get your **Store ID** and **Hash Key**.
3. Paste them into `config/db.php`:
   ```php
   define('EASYPAISA_STORE_ID', '...');
   define('EASYPAISA_HASH_KEY', '...');
   ```
4. Same sandbox-first approach as JazzCash.

### One more required setting
```php
define('APP_URL', 'https://your-real-domain.com/folivo');
```
This **must** be a real, publicly reachable URL once you go live (not
`localhost`) — it's where JazzCash/Easypaisa redirect the customer back
to after payment. Both gateways need to be able to reach your server.

⚠️ **Double-check the exact field names** in `includes/functions.php`
(`jazzcash_build_fields`, `easypaisa_build_fields`) and the callback
handlers against the PDF/API documentation your merchant dashboard gives
you — I've followed each gateway's commonly published integration
pattern, but exact field names can vary slightly by merchant agreement
type and do change over time.

## PDF Export Setup

**Nothing to install.** PDF generation is built directly into this app
(`includes/simple_pdf.php`) — no Composer, no dompdf, no external library.
The only requirement is that PHP's **GD extension** is enabled, which is
on by default in XAMPP and almost every hosting provider. If your PDF
downloads are missing images (text still generates fine), check that GD
is enabled: in `php.ini` look for `extension=gd` and make sure it's not
commented out, then restart Apache.

## Setup Instructions (XAMPP / Local Server)

1. **Install XAMPP** if you don't have it: https://www.apachefriends.org/

2. **Copy the `folivo` folder** into your server's web root:
   - Windows: `C:\xampp\htdocs\folivo`
   - Mac/Linux: `/opt/lampp/htdocs/folivo`

3. **Start Apache and MySQL** from the XAMPP control panel.

4. **Create the database:**
   - Open `http://localhost/phpmyadmin`
   - Click "Import" → choose `database/schema.sql` → click "Go"
   - (Upgrading an existing install? See the "MIGRATION NOTES" comment
     block near the bottom of `database/schema.sql` — it has the exact
     `ALTER TABLE` / `CREATE TABLE` statements for the admin panel and
     the category rename, instead of re-importing everything.)

5. **Check your DB credentials** in `config/db.php` (defaults match a
   fresh XAMPP install: host `localhost`, user `root`, password empty).

6. **PDF export needs no setup** — it's dependency-free (see "PDF Export
   Setup" above). Just make sure PHP's GD extension is enabled.

7. **Add your payment gateway credentials** (see "Payment Gateway Setup"
   above) — the app runs fine without this, but the Upgrade page's
   payment buttons won't work until you do.

8. **Set up your admin login** (see "Admin Panel" above) — visit
   `admin_setup.php` once, then delete it.

9. **Make the uploads folders writable** (already created for you, but
   if you deploy to real hosting, make sure PHP can write to):
   - `assets/uploads/avatars/`
   - `assets/uploads/banners/`
   - `assets/uploads/portfolio/`
   - `assets/uploads/cv/`

10. **Visit the app:**
   - `http://localhost/folivo/` → redirects to login/signup
   - Create an account, complete your profile, upload some work
   - Your public link is `http://localhost/folivo/p.php?slug=your-name`
     (the slug is auto-generated from your name at signup)
   - Admin panel: `http://localhost/folivo/admin/login.php`

## Folder Structure

```
folivo/
├── admin/
│   ├── login.php / logout.php / auth_check.php
│   ├── dashboard.php             (signups, Pro/Trial breakdown, revenue by day/week/month/year)
│   ├── users.php                  (search/filter all users)
│   ├── edit_user.php              (edit details, grant/remove Pro, reset password, delete)
│   ├── delete_user.php
│   ├── payments.php               (full transaction ledger)
│   └── _navbar.php
├── admin_setup.php                 ⚠️ run once, then delete
├── auth/
│   ├── login.php
│   ├── signup.php
│   ├── logout.php
│   └── forgot_password.php
├── dashboard/
│   ├── profile.php              (view + edit profile, avatar, banner, CV, PDF button)
│   ├── portfolio.php             (upload, categorize, bulk actions, delete)
│   ├── _image_card.php           (shared partial for one portfolio image)
│   ├── download_pdf.php          (generates & streams the portfolio PDF — no dependencies)
│   ├── upgrade.php               (plan comparison + payment buttons)
│   ├── jazzcash_callback.php     (verifies JazzCash payment, upgrades account)
│   └── easypaisa_callback.php    (verifies Easypaisa payment, upgrades account)
├── includes/
│   ├── functions.php             (incl. payment hashing + PDF-limit helpers)
│   ├── simple_pdf.php             (dependency-free PDF writer used for portfolio export)
│   ├── auth_check.php
│   └── sidebar.php
├── assets/
│   ├── css/style.css             (ported from your original design + admin panel styles)
│   └── uploads/                  (avatars, banners, portfolio, cv)
├── config/db.php                 ⚠️ WooCommerce-style: your gateway keys go here
├── database/schema.sql
├── p.php                          (public client-facing portfolio view)
└── index.php
```

## Security Notes (Read Before Going Live)

- **Forgot password** currently lets anyone who knows an account's email
  set a new password with no verification — this matches the original
  demo's behavior, but is **not safe for a real production app**. Before
  launching publicly, add email-based OTP or a signed reset-link sent via
  email (e.g. with PHPMailer) so only the real account owner can reset it.
- File uploads are restricted by extension and size, but for extra
  safety on shared hosting, consider also validating actual file content
  (e.g. `getimagesize()` for images) before trusting uploads.
- Change the default `config/db.php` credentials before deploying to a
  live server, and never commit real database/gateway credentials to a
  public repo.
- The payment callback handlers verify each gateway's signed hash before
  trusting a "successful" response — never remove that check, since
  return URLs can otherwise be hit directly by anyone to fake a payment.
