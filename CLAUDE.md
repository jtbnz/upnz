# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

The website for V D Wood Upholstery, live at **https://vdwood.co.nz**. Hand-written HTML/CSS/vanilla-JS pages plus a small PHP layer providing admin login, a SQLite-ordered image gallery, and a contact form backend. No build step, no package manager, no framework — files are edited and served directly.

Despite the repo name (`upnz`) and lingering "Vaughans Upholstery" strings in some markup, the business is V D Wood Upholstery.

## Deployment

**This repository is the document root of the live site.** `vaughan:~/public_html` is a checkout of it (`ssh vaughan`, cPanel shared hosting, jailshell). There is no staging environment and no deploy pipeline — changes reach production by landing files in that directory.

Consequences worth internalising:

- `rsync` is not installed on the server. Transfer with `scp`, or `tar czf - … | ssh`.
- The server's working tree can drift from `origin/main`. Check `git status` there before assuming the repo reflects what is live — it once carried five months of uncommitted work.
- `work_track/` sits inside `public_html` but belongs to a **separate repository** (github.com/jtbnz/work_track). It is gitignored here. Do not commit it, and do not let document-root `.htaccess` rules break it.
- **The GitHub repo is public.** Never describe an unpatched vulnerability in a commit message that gets pushed before the fix is deployed.

## Running locally

PHP pages need a PHP server; opening `examples.php` from the filesystem will not work:

```bash
php -S localhost:8000
```

Production runs **PHP 8.1** (`ea-php81`, set by the cPanel handler in `.htaccess`). Check syntax against that target with `php -l <file>`. There are no tests, linters, or build commands.

## Required config file (not in git)

`auth.php` does `require_once 'config/secrets.php'`, and that file is gitignored — **every PHP page fatals until it exists**. Copy `config/secrets.php.example` and fill it in. It defines `$users` (bcrypt hashes, not plaintext), `$session_timeout`, `$to_email`, `$email_subject_prefix`, and the reCAPTCHA keys plus `$recaptcha_v3_threshold`.

Generate a password hash with:

```bash
php -r "echo password_hash('...', PASSWORD_DEFAULT), PHP_EOL;"
```

## PHP layer

`auth.php` is the entry point every PHP page requires first. It configures and starts the session, defines `isLoggedIn()` / `isAdmin()` / `loginUser()` / `logoutUser()`, and — as a side effect of being included — handles the `action=login` POST and `action=logout` GET, redirecting to `examples.php`. **It must be required before any output**, since it sets cookies and calls `session_regenerate_id()`. Adding auth to a new page means `require_once 'auth.php'` at the very top.

Login is rate limited to 5 attempts per 15 minutes per session (`LOGIN_MAX_ATTEMPTS`, `LOGIN_LOCKOUT_SECONDS`).

- `config/db.php` — `Database` singleton over SQLite at `data/images.db`. Holds one table, `image_order`, mapping filename → `display_order`. `syncWithDirectory()` reconciles the table against what is actually on disk on every page load, so the DB is derived state: deleting `data/` is recoverable, only manual ordering is lost. `data/` is gitignored.
- `examples.php` — public gallery, ordered via the DB. Shows a login modal.
- `admin.php` — guarded by `isAdmin()`. Handles `action=upload` (multi-file), `action=bulk_delete`, and `action=delete`.
- `ajax/reorder.php` — admin-only JSON endpoint; accepts `{order: [{filename}, …]}` and rewrites `display_order`.
- `contact-process.php` — contact form backend. Honeypot field named `website`, reCAPTCHA v3 verification against `$recaptcha_v3_threshold`, then `mail()`. Returns JSON, so **never enable `display_errors` here** — it corrupts the response body and leaks paths.

### Upload security

`images/examples/` is inside the document root and the PHP handler applies recursively, so anything executable landing there runs. Two controls keep that shut, and both must stay:

1. `admin.php` determines the file type with `getimagesize()` and derives the stored extension **from what it detected** — never from `$_FILES['type']` or the client's filename, both of which are attacker-controlled. This exact mistake previously allowed writing a `.php` file into the webroot.
2. `images/examples/.htaccess` removes the PHP handler and serves image extensions only.

The document-root `.htaccess` adds site-wide denials (dotfiles, AppleDouble `._*` sidecars, `.md`/`.db`/`.example`, and `/config/` + `/data/`). **These rules live above the cPanel-generated blocks** — cPanel rewrites its own delimited sections in place, so anything inside those markers is lost on the next panel change.

## Front-end conventions

- Every page repeats its own `<header>` nav and `<footer>` inline — there is no templating. A nav or footer change must be applied to all six pages.
- `css/styles.css` owns the design system as `:root` custom properties (`--primary-color`, `--secondary-color`, `--font-primary`/`--font-secondary`, `--spacing-*`, `--container-width`, `--transition-*`). Use these rather than literal values. `css/examples.css` and `css/admin.css` are page-scoped; `css/normalize.css` is the reset and loads first.
- Page-specific CSS also appears in inline `<style>` blocks in `about.html`, `services.html`, `gallery.html`, and `contact.html`; `gallery.html` additionally carries its inline `<script>` for `data-filter` / `data-category` filtering.
- `js/main.js` loads on every page (hamburger nav, smooth scroll, nav highlighting) and posts the contact form to `contact-process.php` after fetching a reCAPTCHA token. The site key is inline in `main.js`; the secret stays in `secrets.php`.
- `js/examples.js` drives the lightbox and, for admins, drag-to-reorder that PUTs to `ajax/reorder.php`. It reads image sources from the rendered DOM — `examples.php` renders the `<img>` tags server-side.
- `examples.html` is a **legacy** static counterpart to `examples.php` with no login and no DB. Nothing in the nav links to it.
- Google Fonts (Playfair Display, Raleway) and Font Awesome 6 load from CDNs in each page's `<head>`.

## Images

`images/gallery/`, `images/services/`, `images/hero-images/` and the logos are site assets and **are** tracked. `images/examples/` is not: it holds several hundred megabytes of admin-uploaded client photographs, managed entirely through the admin panel, and is gitignored apart from its `.htaccess`.
