# Habit Tracker (PHP)

A fully client-side habit tracker with a PHP 8.2 + SQLite3 backend for authentication, persistence, and cloud sync. Single-page UI (vanilla JS) with profiles, streaks, XP, achievements, partners, and more.

- **Backend:** PHP >= 8.2, SQLite3 (`pdo_sqlite` / `sqlite3` extension)
- **Frontend:** Vanilla HTML/CSS/JS, no build step
- **Auth:** Session-based (30-day persistent cookie), CSRF-protected, rate-limited

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Quickstart (local development)](#quickstart-local-development)
3. [Deploying properly](#deploying-properly)
   - [A. Docker](#a-docker-recommended-for-most-hosts)
   - [B. Render.com](#b-rendercom)
   - [C. Apache 2.4 / shared hosting](#c-apache-24--php)
   - [D. Nginx + PHP-FPM](#d-nginx--php-fpm)
   - [GH Pages (static mirror, demo only)](#github-pages-static-mirror-not-a-real-deploy)
   - [Quick pick: which one for you](#quick-pick)
4. [Storage & backups](#storage--backups)
5. [Security notes](#security-notes)
6. [Project layout](#project-layout)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

- **PHP >= 8.2** with the `sqlite3` (or `pdo_sqlite`) extension enabled
- **SQLite** (created automatically on first run)
- A web server: Apache 2.4, Nginx, Docker, or the PHP built-in server
- **No Composer / Node / build step is required**

Verify locally:

```bash
php -v
php -m | grep -i sqlite
```

Missing SQLite on Debian/Ubuntu:

```bash
sudo apt-get install -y php-sqlite3
```

---

## Quickstart (local development)

```bash
# 1. Initialise the SQLite schema (safe to run multiple times)
php db.php

# 2. Serve the app
php -S localhost:8080 router.php
```

Open <http://localhost:8080>.

> **IMPORTANT — always use `router.php` with the built-in server.**
> A bare `php -S localhost:8080` will serve the whole directory, exposing
> `/data/habit_tracker.db` and `/data/rate_limits.json` to anyone. The
> router blocks `/data/` and all dotfiles (`/.htaccess` only works on Apache).

---

## Deploying properly

### A. Docker (recommended for most hosts)

The included `Dockerfile` uses `php:8.2-apache` with `mod_rewrite` and
`pdo_sqlite`, and makes `data/` writable by the web user. The shipped
`.htaccess` already blocks `/data/`.

```bash
docker build -t habit-tracker .
docker run -d -p 8080:80 --name habit-tracker habit-tracker
```

- App: <http://localhost:8080>
- DB persists only inside the container volume while the container lives. Mount a
  volume for production data:

```bash
docker run -d -p 8080:80 -v habit-data:/var/www/html/data --name habit-tracker habit-tracker
```

### B. Render.com

`render.yaml` (Docker runtime) is included:

```yaml
services:
  - type: web
    name: habit-tracker
    runtime: docker
    dockerfilePath: Dockerfile
    envVars:
      - key: PORT
        value: 8080
    healthCheckPath: /login.php
```

Deploy:

1. Push this repo to GitHub (e.g. `Habit-Tracker-Fixed`).
2. Render → **New → Blueprint** → pick the repo.
3. Render builds the Docker image and deploys; health check hits `/login.php`.

> **Datastore note:** SQLite lives inside the container. For multiple instances
> or restarts, add a mounted disk (Render → your service → Disks → mount at
> `/var/www/html/data`).

### C. Apache 2.4 / PHP (shared hosting or VPS)

1. Drop the project files into your webroot, e.g. `/var/www/html/habit-tracker/`.
2. Ensure `mod_rewrite` and the PHP SQLite extension are enabled.
3. Make `data/` writable by the web server process:

```bash
chown -R www-data:www-data data
```

4. Optionally run `php db.php` once to pre-create the schema.

The shipped `.htaccess` handles the rest:

- Rewrites non-asset GET requests to `index.php`
- Returns `403 Forbidden` for anything under `/data/`

If `.htaccess` is ignored (AllowOverride None), add the two rules to your
`<VirtualHost>` / directory block directly.

### D. Nginx + PHP-FPM

Nginx does **not** read `.htaccess`, so the `/data/` block and the rewrite must
be configured explicitly. Example server block:

```nginx
server {
    listen 80;
    server_name habit.example.com;
    root /var/www/habit-tracker;
    index index.php;

    # Never serve the database or rate-limit file
    location ^~ /data/ { deny all; }

    # Never serve dotfiles (e.g. .htaccess, .git)
    location ~ /\. { deny all; }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Make sure the FPM pool user owns `data/` and that `session.gc_maxlifetime` /
cookie lifetime (already 30 days in `config.php`) survive restarts — they do.

### GitHub Pages (static mirror — NOT a real deploy)

`index-ghpages.html` / `_config.yml` exist so the static UI can be previewed on
GitHub Pages. The PHP auth, sessions, persistence, and API do **not** run there.
Use it only as a UI demo, never as the production backend.

### Quick pick

| Situation                        | Use                          |
|----------------------------------|------------------------------|
| Local dev / rapid testing        | `php -S localhost:8080 router.php` |
| VPS with Apache                 | Option C (`.htaccess` included) |
| VPS with Nginx                  | Option D (deny `/data/`!)    |
| Small production / one-click    | Option B (Render + mounted disk) |
| Containerized / any Docker host | Option A (Docker)            |

---

## Storage & backups

- **Database:** `data/habit_tracker.db` (SQLite, created on first run by `config.php`)
- **Rate limiter:** `data/rate_limits.json` (auto-pruned; failure records only)
- Both are **gitignored**, so user data never enters the repository.

Automated backup script included (`scripts/backup.sh`):

```bash
chmod +x scripts/backup.sh
# cron: nightly DB snapshot, keeps 30 days
0 2 * * * /path/to/habit-tracker/scripts/backup.sh
```

Remember to back up the RAR/tar archives of the whole project too (as done for
the release snapshots).

---

## Security notes

- `/data/` is **never** web-reachable: Apache `.htaccess` blocks it; `router.php`
  blocks it under the built-in server; Nginx needs the explicit `deny all` block
  above.
- Sessions persist 30 days (`config.php`) with `HttpOnly`, `SameSite=Lax`, and
  `Secure` when served over HTTPS; the session id is regenerated on login and
  after 30 minutes of inactivity.
- Mutating API actions (`reset`, `delete`, `notifications`, `invite`, `join`,
  partner disconnect) require a CSRF token (`_csrf` / `X-CSRF-Token` header).
- Login and password-reset are rate-limited (10 attempts / 15 min IP bucket).
- Passwords are stored as bcrypt hashes; reset tokens are never logged.
- No `.env` secrets needed — all configuration is hardcoded and secret-free.

---

## Project layout

```
habit_tracker/
├── config.php          # sessions, CSRF, rate limiter, security headers, DB path
├── router.php          # php -S router: blocks /data/ + dotfiles
├── auth.php            # login, signup, logout, reset, delete account
├── api.php             # data API: load/save/delete/reset/export/invite/etc.
├── index.php           # authenticated app shell + service worker wiring
├── login.php           # login screen
├── signup.php          # signup screen
├── settings.php        # profile, password, data reset
├── logout.php          # destroys the server session
├── export.php          # JSON export download
├── db.php              # schema init / migration
├── habit-tracker-v2.html  # static UI source served by index.php
├── service-worker.js   # offline cache (static assets only)
├── manifest.json       # PWA manifest (relative paths)
├── landing.php         # marketing landing page
├── 404.php             # custom 404
├── .htaccess           # Apache: rewrite + /data block
├── Dockerfile          # php:8.2-apache + pdo_sqlite
├── render.yaml         # Render.com blueprint (Docker runtime)
├── deploy.sh           # VPS setup helper (PHP + SQLite + db init)
└── data/               # gitignored: habit_tracker.db, rate_limits.json
```

---

## Troubleshooting

| Symptom                                   | Likely cause & fix                                   |
|-------------------------------------------|------------------------------------------------------|
| `/data/habit_tracker.db` returns 200 in browser | You ran `php -S` without `router.php` (or Nginx has no `deny all`). Fix per Quickstart / Option D. |
| Logged out after closing the browser      | Cookie was cleared (old cookie), or Server sets `Secure` over HTTP. Serve over HTTPS. |
| Blank page                                | PHP SQLite extension missing. `php -m \| grep -i sqlite`. |
| 403 on `/login.php`                       | `.htaccess` rewrite rule conflict on shared hosting. Check `AllowOverride All`. |
| Sign-up/account issues                    | Schema drift. Run `php db.php` to apply migrations. |
| DB not writable                           | `data/` owned by wrong user. `chown -R www-data:www-data data`. |