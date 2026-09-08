# Sea to Summit Trekking — PHP/MySQL Website

A full working clone of the site in your screenshots: home page, treks listing
with search/filter, a trek detail page with a live-pricing customise panel,
a booking form backed by MySQL, a customer "My Bookings" lookup, and an
admin login + dashboard to manage bookings.

## 1. Run with Docker

Install Docker Desktop, then run these commands from the project folder:

```bash
docker compose up -d --build
```

Open `http://localhost:8080`. The PHP app and MySQL database start together;
the first database startup imports `db.sql` automatically. MySQL data is kept
in the `mysql_data` Docker volume. The app service mounts the project folder,
so saved PHP, HTML, CSS, JavaScript, and image changes are available without
rebuilding the image.

To stop the containers:

```bash
docker compose down
```

To remove the database and import `db.sql` again on the next startup:

```bash
docker compose down -v
docker compose up -d --build
```

The first database container startup imports `db.sql` automatically. The
database is stored in the `mysql_data` Docker volume, so it survives app
rebuilds. To reset it and import the schema again:

```bash
docker compose down -v
docker compose up -d --build
```

## 2. Run the site

Visit: `http://localhost:8080/index.html`

The included Docker image provides Apache, PHP, `mysqli`, cURL, GD, and the
upload configuration required by the application. No local PHP or MySQL
installation is needed.

## 4. Admin login

Go to `http://localhost:8080/login.php`

- Username: `admin`
- Password: `admin123`

You'll land on the admin panel, which has three sections in the sidebar:

- **Bookings** (`admin/dashboard.php`) — every booking, with a dropdown to
  change its status (Pending / Confirmed / Cancelled).
- **Treks** (`admin/treks.php`) — a table of every trek with Edit/Delete
  buttons, and an "Add New Trek" button. Deleting a trek also deletes its
  bookings (you'll get a confirmation warning first).
- **Edit Website** (`admin/settings.php`) — change the homepage hero text,
  stats numbers, "Why Us" section, call-to-action text, site name, WhatsApp
  number, group discount percentages, and footer text — all without touching any code. There's also a
  section at the bottom to change your admin password.

### Adding / editing a trek

`admin/treks.php` → **Add New Trek** (or **Edit** on an existing one) opens
a form covering:
- Basic info (title, region, difficulty, duration, price, badge, image URL)
- Description & highlights
- Itinerary (one line per day)
- What's included (one line per item)
- **Accommodation options** — add as many as you like, mark one as the
  default (usually the $0 / included one) with the radio button on the left
- **Transportation options** — same idea

Saving replaces that trek's accommodation/transport option list with
whatever rows are on the form, so you can freely add or remove rows each
time you edit.

### If you already set up the database before this update

Run the migration that matches what you're upgrading from with Docker:

```bash
Get-Content upgrade_v6.sql | docker compose exec -T db mysql -u root -proot_password_change_me seatosummit
```

Replace `upgrade_v6.sql` with the migration you need and run migrations **in
order**:

- Coming from the very first version (no admin panel at all) → import
  `upgrade_settings.sql`, then `upgrade_v2.sql`, then `upgrade_v3.sql`,
  then `upgrade_v4.sql`, then `upgrade_v5.sql`, then `upgrade_v6.sql`.
- Coming from the admin-CMS version (treks/settings editor, no customer
  accounts yet) → import `upgrade_v2.sql`, then `upgrade_v3.sql`, then
  `upgrade_v4.sql`, then `upgrade_v5.sql`, then `upgrade_v6.sql`.
- Coming from the customer-accounts version (had `account.php` etc, but
  accommodation/transport were still per-trek only) → import
  `upgrade_v3.sql`, then `upgrade_v4.sql`, then `upgrade_v5.sql`, then
  `upgrade_v6.sql`.
- Coming from the catalog/social-login/rich-text version (had
  Accommodations/Transports admin pages and "Continue with Google") →
  import `upgrade_v4.sql`, then `upgrade_v5.sql`, then `upgrade_v6.sql`.
- Coming from the Tours/logo-upload/GHT-section version (had a working
  Tours nav item and Edit Website → Site Logo) → import `upgrade_v5.sql`,
  then `upgrade_v6.sql`.
- Coming from the first GHT-map version (hand-drawn SVG-style map with
  11 plain dot markers) → just import `upgrade_v6.sql`.
- Starting completely fresh → just import `db.sql`, it has everything.

⚠️ **`upgrade_v3.sql` restructures core tables — back up your database first**
with:

```bash
docker compose exec -T db mysqldump -u root -proot_password_change_me seatosummit > backup.sql
```

It preserves your existing
treks and bookings, but your treks' old itinerary text is set aside
(renamed to `itinerary_legacy`, not deleted) since the site now reads
from the new structured `itinerary_days` table — you'll want to re-enter
each trek's day-by-day itinerary once via Admin → Treks → Edit after
upgrading. See the comments at the top of `upgrade_v3.sql` for details.

`upgrade_v4.sql` is lower-risk — it just adds a `product_type` column
(defaulting every existing row to `'trek'`, so nothing you have changes)
and some new settings, plus two sample tours you can delete from Admin →
Tours if you don't want them.

`upgrade_v5.sql` and `upgrade_v6.sql` are both content-only (new blog
posts) — but each also needs files copied from this version's zip into
your existing `assets/images/` folder by hand, since SQL can't add
files. See the comment block at the top of `upgrade_v6.sql` for exactly
which files.

⚠️ **Change the admin password before putting the site anywhere public.**
To set a new one, generate a new hash with PHP:
```php
<?php echo password_hash('yourNewPassword', PASSWORD_DEFAULT);
```
and update the `password_hash` column for the `admin` row in the `admins`
table using the Docker MySQL client.

## 5. What's on the site now

**Public pages:**
- `index.html` — home page: hero (image or **video background** — see
  below), stats, a **Great Himalayan Trail** showcase section (stylised
  Nepal map with major peaks and the west-to-east route — see below),
  Featured Treks, Popular Tours (shown once you have at least one), Why
  Us, and a Google-style reviews section
- `treks.php` / `tours.php` / `trek-detail.php` — browse & customise
  treks and tours (tours use the same detail page and booking flow —
  think cultural tours, jungle safaris, city experiences, as opposed to
  multi-day mountain treks). The Overview tab supports rich text
  (formatting + inline images) written by the admin, and the Itinerary
  tab is an expandable day-by-day accordion
- `travel-options.php` — every accommodation & transport option in your
  reusable catalog ("Hotels & Transport" in the nav)
- `about.php` — About Us page (fully editable from admin)
- `info.php` / `post-detail.php` — announcements & trail-condition posts
  ("Info" in the nav) — a general-purpose place for you to post updates
- `account-register.php` / `account-login.php` / `account.php` — customer
  accounts, with email/password **and** "Continue with Google/Facebook"
  login (once configured — see below). Logged-in customers can edit or
  delete their own bookings from `account.php`; guests can still book
  without an account and look up a booking read-only via
  `my-bookings.php`

**Admin panel (`/admin`, login required):**
- **Bookings** — every booking, with status management
- **Treks** / **Tours** — add/edit/delete either type from the same
  form (a Type dropdown at the top), with a rich-text description &
  highlights editor, a day-by-day itinerary builder, and an
  accommodation/transport picker (see below)
- **Accommodations** / **Transports** — your own reusable catalog,
  managed independently (add/edit/delete), each with a name, description,
  photo, and default price
- **Customers** — read-only list of registered accounts
- **Reviews** — the homepage Google-style reviews
- **Info Posts** — the Info page
- **Edit Website** — logo upload, homepage video upload, homepage/About
  Us text and images, the Great Himalayan Trail section, Google Reviews
  settings, and Social Login credentials

### Logo

Admin → Edit Website → Site Logo. Upload once (PNG with a transparent
background works best) and a white/transparent cutout version for the
dark header, sidebar, and login screens is generated automatically —
you don't need to prepare two versions yourself. "Reset to default logo"
reverts to the logo already installed on the site.

### Homepage video

Admin → Edit Website → Homepage Video. Upload an MP4/WebM/MOV and it
plays muted, autoplay, and looped as the hero background instead of the
static image (the image is still used as the video's poster frame, and
stays the fallback if you remove the video later). Keep files
compressed. The Docker image sets `upload_max_filesize` and `post_max_size`
to 64MB; rebuild the app container after changing those values in
`Dockerfile`.

### Great Himalayan Trail section

A dedicated homepage section built around your actual GHT route map
(`assets/images/ght-map.png`), with 15 clickable markers running the
length of the route — each one is that peak or lake's own artwork,
shown as a small round button that lifts and glows on hover. Hover or
tap a marker to see its name and elevation; clicking it opens that
location's own blog post: Phewa Lake, Tilicho Lake, Annapurna, Manaslu,
Langtang Lirung, Kyanjin Ri, Cho Oyu, Gokyo Lakes, Ama Dablam, Mera Peak,
Mount Everest, Lhotse, Baruntse, Makalu, and Kanchenjunga — each editable
exactly like any other post under Admin → Info Posts (their marker icons
also become that post's header image automatically).

The heading, description, and the three stat callouts underneath
(distance, days, ranges crossed) are editable under Admin → Edit Website
→ Great Himalayan Trail Section. The map image and marker positions
themselves are **not** admin-editable — the 15 markers are calibrated to
percentage positions measured along the actual route line in that
specific image (green/brown/cyan segments = west/central/east), so
swapping the map image would need the coordinates in `index.html`
(`$ghtPeaks` near the top of the GHT section) re-measured to match. The
small marker icons live in `assets/images/mountains/` (one PNG per
peak/lake, cropped square from the full artwork) and the full-size
versions used as blog headers live in `assets/images/mountains/full/`.
A few markers reuse posts created under the previous map version whose
slugs don't quite match the new icon filenames (`langtang`/`mount-everest`/
`kangchenjunga` vs. `langtang-lirung`/`everest`/`kanchenjunga`) — that
mapping is handled by the `$ghtIconOverrides` array right above
`$ghtPeaks`, so if you rename a post's slug later, check that array too.

### Accommodation & transport: how the catalog works

Instead of typing hotel/transport options separately for every trek,
**Accommodations** and **Transports** are now a shared catalog (own admin
pages, own add/edit/delete). When you add or edit a trek or tour, you:
1. **Check the box** next to any existing catalog item to attach it to
   this trek, and optionally override its price just for this trek.
2. **Pick one attached item as the default** (radio button) — that's the
   one shown as pre-selected/included on the trek page.
3. Or **add a brand new one inline** at the bottom of that section — it's
   saved to the catalog automatically, so it's available to attach to
   other treks/tours next time too.

### Social login setup

Email/password login works out of the box. To turn on "Continue with
Google" and "Continue with Facebook," go to Admin → Edit Website →
Social Login. You'll need to create your own app in the
[Google Cloud Console](https://console.cloud.google.com/apis/credentials)
and/or [Facebook for Developers](https://developers.facebook.com/apps) —
that page shows you the exact redirect URI to whitelist in each console.
Leave the fields blank to keep those buttons hidden.

Note: true live Google Reviews (the star-rating widget) requires a paid
Google Places API key, so that section ships as an admin-editable reviews
showcase styled like Google reviews, plus a "See all reviews on Google"
link you can point at your real Google Business profile (Edit Website →
Google Reviews) — this is separate from the Google *login* button above.

## 6. Folder structure

```
seatosummit/
├── admin/
│   ├── includes/
│   │   ├── header.php        Admin sidebar layout (top)
│   │   └── footer.php        Admin sidebar layout (bottom)
│   ├── dashboard.php         Bookings management (login required)
│   ├── treks.php / tours.php Trek / Tour lists w/ edit & delete (share trek-form.php)
│   ├── trek-form.php         Add/edit trek OR tour — Type dropdown, rich text, itinerary builder, catalog picker
│   ├── trek-delete.php       Trek/Tour delete handler
│   ├── accommodations.php / accommodation-form.php / accommodation-delete.php   Accommodation catalog CRUD
│   ├── transports.php / transport-form.php / transport-delete.php               Transport catalog CRUD
│   ├── customers.php         Read-only list of registered customer accounts
│   ├── reviews.php / review-form.php / review-delete.php   Manage homepage reviews
│   ├── posts.php / post-form.php / post-delete.php         Manage the Info page posts
│   └── settings.php          "Edit Website" — logo/video upload, content editor, Social Login, password
├── assets/
│   ├── css/style.css         All styling (dark navy + blue theme, incl. admin layout)
│   ├── js/main.js            Mobile menu, tabs, filters, live price calc, delete confirms
│   ├── js/admin.js           Dynamic rows, slug auto-fill, Quill rich-text editor setup
│   ├── images/                Your logo (color + white versions, full and mark-only) + favicon +
│   │                           any logo uploaded via admin + ght-map.png (GHT route map)
│   │   └── mountains/          Small marker icons (mountains/*.png) + full-size blog
│   │       └── full/            header images (mountains/full/*.png) for each GHT peak/lake
│   └── videos/                Homepage hero videos uploaded via admin
├── includes/
│   ├── header.php            Shared nav header
│   └── footer.php            Shared footer + WhatsApp floating button
├── config.php                 DB connection + auth/OAuth/upload helpers (get_setting, slugify,
│                               site_base_url, oauth_http_get/post, social_login_upsert,
│                               process_logo_upload, process_video_upload)
├── db.sql                     Full schema + seed data (import this first, for a fresh install!)
├── upgrade_settings.sql       Migration: pre-admin-panel → admin-panel version
├── upgrade_v2.sql             Migration: admin-panel version → customer-accounts version
├── upgrade_v3.sql             Migration: → catalog/social-login/rich-text version
├── upgrade_v4.sql             Migration: → Tours/logo-upload/GHT-section version
├── upgrade_v5.sql             Migration: → GHT map v1 version (11 dot markers)
├── upgrade_v6.sql             Migration: → this version (GHT map v2 — real route map, 15 artwork buttons)
├── index.html                 Home page (hero video, GHT map, Featured Treks, Popular Tours, reviews)
├── treks.php / tours.php      Listings (search + filter by difficulty/duration), same UI for both
├── trek-detail.php            Single trek/tour page — rich content, itinerary accordion, booking panel
├── travel-options.php         "Hotels & Transport" — browse the accommodation/transport catalog
├── about.php                  About Us page (settings-driven)
├── info.php / post-detail.php Info/announcements list + single post view
├── book.php                   Handles booking form submission
├── my-bookings.php            Guest lookup of bookings by email (read-only)
├── account-register.php       Customer sign-up (email/password)
├── account-login.php          Customer sign-in (email/password + social buttons)
├── account-logout.php         Customer sign-out
├── account.php                Customer dashboard — view/edit/delete own bookings
├── booking-edit.php           Edit a booking (ownership-checked)
├── booking-delete.php         Delete a booking (ownership-checked)
├── oauth-google.php / oauth-google-callback.php       Google login flow
├── oauth-facebook.php / oauth-facebook-callback.php   Facebook login flow
├── login.php                  Admin login
├── logout.php                 Admin logout
└── README.md                  This file
```

## 7. Customising

- **WhatsApp number**: edit under Admin → Edit Website → General (not in
  code) — `WHATSAPP_NUMBER` in `config.php` is only used as a fallback.
- **Treks**: manage entirely from Admin → Treks — no need to touch the
  database directly.
- **Accommodation / transport catalog**: manage from Admin →
  Accommodations / Transports.
- **Logo**: your logo is already installed at `assets/images/logo-*.png`
  (a white cutout version for the dark header/sidebar, plus the full-color
  original). To replace it, drop in new files with the same names.
- **Colours/fonts**: all in `assets/css/style.css` — the palette is defined
  as CSS variables at the very top of the file (`--navy`, `--blue`, etc).

## Notes

- Passwords are hashed with PHP's `password_hash()`/`password_verify()` —
  never stored in plain text. Social-login-only accounts simply have no
  password set until the customer adds one.
- All database queries use prepared statements (`mysqli` with bound
  parameters) to prevent SQL injection.
- Booking totals are recalculated **server-side** in `book.php` and
  `booking-edit.php` from the database (not trusted from the browser), so
  the price a customer submits can't be tampered with via dev tools.
- Customers can only edit/delete a booking that belongs to their account
  (`customer_id`) or was made under their exact account email as a guest
  before they signed up — `booking-edit.php` and `booking-delete.php`
  check ownership on every request, not just the account link.
- The `WHATSAPP_NUMBER` constant in `config.php` is only a fallback now —
  the number shown on the site is whatever's set in Edit Website → General.

## 8. Deploy from GitHub with Railway

GitHub Pages cannot execute PHP or provide MySQL. To deploy the full site,
connect this GitHub repository to a PHP-capable service such as Railway.

1. Create a new Railway project and deploy this repository. Railway will use
  the included `Dockerfile` to build the PHP/Apache application.
2. Add a MySQL service to the same Railway project.
3. Set these variables on the application service. Use the matching values
  supplied by the Railway MySQL service:
  `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, and `MYSQLDATABASE`.
  The application also accepts the equivalent `DB_HOST`, `DB_PORT`,
  `DB_USER`, `DB_PASS`, and `DB_NAME` variables.
4. Import `db.sql` into the Railway MySQL database, then deploy the app.
5. Add your production domain in Railway and update the `CNAME` file only if
  you are using a custom domain. OAuth providers must use the new production
  callback URLs shown by the application.

The included `.htaccess` keeps `index.html` working as a PHP entry point under
Apache. Do not deploy this application with GitHub Pages; it would expose the
PHP source instead of executing it.
- The Google/Facebook login flow uses the cURL extension, which is installed
  in the Docker image. If login fails immediately with a fatal error, rebuild
  the app image with `docker compose up -d --build app`.
- Logo upload (and its automatic white-version generation) uses the GD
  extension, which is installed in the Docker image. Uploaded files are
  stored in `assets/images/` and `assets/videos/` inside the app container.
- The rich-text editor (Quill) and its stylesheet load from a public CDN
  (`cdnjs.cloudflare.com`), so the trek-editing screen needs an internet
  connection — the public-facing site itself does not.
